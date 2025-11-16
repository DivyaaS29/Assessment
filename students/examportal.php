<?php
// students/examportal.php - exam UI (1 question per page)

session_start();
if (!isset($_SESSION['uname'])) {
    header("Location: ../login_student.php");
    exit;
}

require_once __DIR__ . '/../config.php';
date_default_timezone_set('Asia/Kolkata');

// SHOW ERRORS WHILE DEVELOPING
error_reporting(E_ALL);
ini_set('display_errors', 1);

$uname = $_SESSION['uname'] ?? '';

// --------------------------------------------------
// 1. Get EXID (from POST from exams.php OR from GET)
// --------------------------------------------------
$exid = $_POST['exid'] ?? ($_GET['exid'] ?? '');

if (empty($exid)) {
    echo "Missing EXID in URL or POST.";
    exit;
}

// --------------------------------------------------
// 2. Fetch exam from exm_list
//    exm_list(exid, exname, subject, desp, nq, extime, subt)
// --------------------------------------------------
$exam = null;
if ($stmt = $conn->prepare("SELECT exid, exname, extime, subt, nq FROM exm_list WHERE exid = ? LIMIT 1")) {
    $stmt->bind_param('s', $exid);
    $stmt->execute();
    $res  = $stmt->get_result();
    $exam = $res->fetch_assoc();
    $stmt->close();
}

if (!$exam) {
    echo "Exam not found for EXID = " . htmlspecialchars($exid, ENT_QUOTES, 'UTF-8');
    exit;
}

// --------------------------------------------------
// 3. Timer: use subt (exam end time) as deadline
// --------------------------------------------------
$now  = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
$subt = new DateTime($exam['subt'], new DateTimeZone('Asia/Kolkata'));
$diffSec = $subt->getTimestamp() - $now->getTimestamp();

// Fallback demo: if subt is past, give 30 min
if ($diffSec <= 0) {
    $diffSec = 30 * 60;
}

// --------------------------------------------------
// 4. Fetch questions from qstn_list
//    qstn_list(id, exid, qno, qns, op1, op2, op3, op4, correct)
// --------------------------------------------------
$questions = [];

if ($stmt = $conn->prepare("
    SELECT id, qno, qns, op1, op2, op3, op4
    FROM qstn_list
    WHERE exid = ?
    ORDER BY qno ASC
")) {
    $stmt->bind_param('s', $exid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $questions[] = [
            'qid'     => (int)$row['id'],      // internal question ID
            'qno'     => (int)$row['qno'],     // display number
            'text'    => $row['qns'],
            'options' => [
                $row['op1'],
                $row['op2'],
                $row['op3'],
                $row['op4'],
            ],
        ];
    }
    $stmt->close();
} else {
    echo "DB error while preparing question query.";
    exit;
}

if (count($questions) === 0) {
    echo "No questions found in qstn_list for EXID = " . htmlspecialchars($exid, ENT_QUOTES, 'UTF-8');
    exit;
}

// Encode questions for JS
$questionsJson = json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Include header
include_once __DIR__ . '/../header.php';
?>

<div class="exam-wrapper my-3">
  <style>
    body {
      background: #f5f5f5;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .exam-wrapper {
      max-width: 1100px;
      margin: 0 auto;
    }
    .exam-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.08);
      padding: 20px;
    }
    .question-text {
      font-size: 1.05rem;
      font-weight: 500;
      margin-bottom: 10px;
    }
    .option-item {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 6px;
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .option-item input {
      margin-top: 0;
    }
    .option-item:hover {
      background: #f3f3f3;
      border-color: #bbb;
    }
    .question-nav-btns .btn {
      min-width: 110px;
    }

    .palette-card, .calc-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.08);
      padding: 16px;
      margin-bottom: 12px;
    }
    .palette-title {
      font-weight: 600;
      margin-bottom: 8px;
    }
    .qn-btn {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      border: none;
      margin: 4px;
      font-size: 0.85rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .qn-not-visited {
      background: #e0e0e0;
      color: #555;
    }
    .qn-not-answered {
      background: #ffcccc;
      color: #700;
    }
    .qn-answered {
      background: #c8f7c5;
      color: #205723;
    }
    .qn-review {
      background: #ffe8a1;
      color: #805400;
    }
    .qn-current {
      box-shadow: 0 0 0 3px #333;
    }
    .legend-dot {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 4px;
    }

    .calc-display {
      width: 100%;
      height: 38px;
      border-radius: 6px;
      border: 1px solid #ccc;
      padding: 4px 8px;
      text-align: right;
      font-size: 0.95rem;
      margin-bottom: 8px;
    }
    .calc-btn {
      width: 40px;
      height: 32px;
      margin: 2px;
      border-radius: 6px;
      border: 1px solid #ccc;
      background: #f9f9f9;
      cursor: pointer;
      font-size: 0.85rem;
    }
    .calc-btn-op {
      background: #e0e7ff;
    }
    .calc-btn-eq {
      background: #c8f7c5;
    }
    .calc-btn-clr {
      background: #ffcccc;
    }
    @media (max-width: 992px) {
      .exam-wrapper {
        padding: 0 10px;
      }
      .qn-btn {
        width: 30px;
        height: 30px;
        font-size: 0.75rem;
      }
    }
  </style>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">
        <?php echo htmlspecialchars($exam['exname'], ENT_QUOTES, 'UTF-8'); ?>
      </h4>
      <div class="text-muted small">
        Exam ID: <?php echo htmlspecialchars($exam['exid'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    </div>
    <div class="text-end">
      <div class="text-muted small">
        Time left: <span id="timeLeft">--:--</span>
      </div>
      <div class="text-muted small">
        Submit by: <?php echo htmlspecialchars($exam['subt'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    </div>
  </div>

  <!-- IMPORTANT: this posts to students/submit_exam.php -->
  <form id="examForm" method="post" action="submit_exam.php">
    <input type="hidden" name="exid" value="<?php echo htmlspecialchars($exam['exid'], ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="answers_json" id="answers_json">
    <input type="hidden" name="duration_over" id="duration_over" value="0">

    <div class="row g-3">
      <!-- Left: question area -->
      <div class="col-lg-8">
        <div class="exam-card">
          <div id="questionNumber" class="mb-1 text-muted small">Question 1 of N</div>
          <div id="questionText" class="question-text">Loading question...</div>

          <div id="optionsContainer" class="mb-3"></div>

          <div class="question-nav-btns d-flex flex-wrap gap-2">
            <button type="button" id="prevBtn" class="btn btn-outline-secondary btn-sm">Previous</button>
            <button type="button" id="nextBtn" class="btn btn-primary btn-sm">Save &amp; Next</button>
            <button type="button" id="reviewBtn" class="btn btn-warning btn-sm">Mark for Review</button>
            <button type="button" id="clearBtn" class="btn btn-outline-danger btn-sm">Clear Response</button>
            <div class="ms-auto d-flex gap-2">
              <button type="button" id="reviewSubmitBtn" class="btn btn-outline-success btn-sm">
                Review &amp; Submit
              </button>
              <button type="button" id="submitBtn" class="btn btn-danger btn-sm">
                Final Submit
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: question palette + calculator -->
      <div class="col-lg-4">
        <div class="palette-card mb-3">
          <div class="palette-title">Question Status</div>
          <div id="questionPalette" class="mb-2"></div>
          <div class="small text-muted">
            <div><span class="legend-dot" style="background:#e0e0e0;"></span>Not visited</div>
            <div><span class="legend-dot" style="background:#ffcccc;"></span>Not answered</div>
            <div><span class="legend-dot" style="background:#c8f7c5;"></span>Answered</div>
            <div><span class="legend-dot" style="background:#ffe8a1;"></span>Marked for review</div>
          </div>
        </div>

        <div class="calc-card">
          <div class="fw-semibold mb-1">Calculator</div>
          <input type="text" id="calcDisplay" class="calc-display" readonly>
          <div>
            <button type="button" class="calc-btn calc-btn-clr" data-val="C">C</button>
            <button type="button" class="calc-btn calc-btn-clr" data-val="CE">CE</button>
            <button type="button" class="calc-btn calc-btn-op" data-val="/">/</button>
            <button type="button" class="calc-btn calc-btn-op" data-val="*">*</button>
          </div>
          <div>
            <button type="button" class="calc-btn" data-val="7">7</button>
            <button type="button" class="calc-btn" data-val="8">8</button>
            <button type="button" class="calc-btn" data-val="9">9</button>
            <button type="button" class="calc-btn calc-btn-op" data-val="-">-</button>
          </div>
          <div>
            <button type="button" class="calc-btn" data-val="4">4</button>
            <button type="button" class="calc-btn" data-val="5">5</button>
            <button type="button" class="calc-btn" data-val="6">6</button>
            <button type="button" class="calc-btn calc-btn-op" data-val="+">+</button>
          </div>
          <div>
            <button type="button" class="calc-btn" data-val="1">1</button>
            <button type="button" class="calc-btn" data-val="2">2</button>
            <button type="button" class="calc-btn" data-val="3">3</button>
            <button type="button" class="calc-btn calc-btn-eq" data-val="=">=</button>
          </div>
          <div>
            <button type="button" class="calc-btn" style="width: 86px;" data-val="0">0</button>
            <button type="button" class="calc-btn" data-val=".">.</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
// Questions from PHP
const questions = <?php echo $questionsJson; ?>;
const totalQuestions = questions.length;

// State arrays
const answers = new Array(totalQuestions).fill(null);   // selected option index
const status  = new Array(totalQuestions).fill("not_visited");
let currentIndex = 0;

// DOM refs
const questionNumberEl = document.getElementById("questionNumber");
const questionTextEl   = document.getElementById("questionText");
const optionsContainer = document.getElementById("optionsContainer");
const prevBtn          = document.getElementById("prevBtn");
const nextBtn          = document.getElementById("nextBtn");
const reviewBtn        = document.getElementById("reviewBtn");
const clearBtn         = document.getElementById("clearBtn");
const reviewSubmitBtn  = document.getElementById("reviewSubmitBtn");
const submitBtn        = document.getElementById("submitBtn");
const paletteEl        = document.getElementById("questionPalette");
const timeEl           = document.getElementById("timeLeft");
const formEl           = document.getElementById("examForm");
const answersInput     = document.getElementById("answers_json");
const durationOverInput= document.getElementById("duration_over");

// Render one question
function renderQuestion(idx) {
  const q = questions[idx];
  const displayNum = q.qno || (idx + 1);

  questionNumberEl.textContent = `Question ${displayNum} of ${totalQuestions}`;
  questionTextEl.textContent   = q.text;

  optionsContainer.innerHTML = "";
  q.options.forEach((opt, optIndex) => {
    const wrap = document.createElement("label");
    wrap.className = "option-item";

    const input = document.createElement("input");
    input.type = "radio";
    input.name = "option";
    input.value = optIndex;

    if (answers[idx] === optIndex) {
      input.checked = true;
    }

    input.addEventListener("change", () => {
      answers[idx] = optIndex;
      if (status[idx] === "not_visited" || status[idx] === "not_answered") {
        status[idx] = "answered";
      }
      updatePalette();
    });

    const span = document.createElement("span");
    span.textContent = opt;

    wrap.appendChild(input);
    wrap.appendChild(span);
    optionsContainer.appendChild(wrap);
  });

  prevBtn.disabled = idx === 0;
  nextBtn.disabled = idx === totalQuestions - 1;
  updatePalette();
}

function getCurrentSelection() {
  const radios = document.querySelectorAll("input[name='option']");
  for (const r of radios) {
    if (r.checked) return parseInt(r.value, 10);
  }
  return null;
}

// Palette
function buildPalette() {
  paletteEl.innerHTML = "";
  for (let i = 0; i < totalQuestions; i++) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "qn-btn";
    const displayNum = questions[i].qno || (i + 1);
    btn.textContent = displayNum;
    btn.dataset.index = i;

    btn.addEventListener("click", () => {
      if (status[i] === "not_visited") {
        status[i] = answers[i] === null ? "not_answered" : "answered";
      }
      currentIndex = i;
      renderQuestion(currentIndex);
    });

    paletteEl.appendChild(btn);
  }
  updatePalette();
}

function updatePalette() {
  const btns = paletteEl.querySelectorAll(".qn-btn");
  btns.forEach(btn => {
    const idx = parseInt(btn.dataset.index, 10);
    btn.className = "qn-btn";

    switch (status[idx]) {
      case "not_visited":
        btn.classList.add("qn-not-visited");
        break;
      case "not_answered":
        btn.classList.add("qn-not-answered");
        break;
      case "answered":
        btn.classList.add("qn-answered");
        break;
      case "review":
        btn.classList.add("qn-review");
        break;
    }

    if (idx === currentIndex) {
      btn.classList.add("qn-current");
    }
  });
}

// Save current selection
function saveCurrentAnswer() {
  const sel = getCurrentSelection();
  answers[currentIndex] = sel;
  if (status[currentIndex] !== "review") {
    status[currentIndex] = sel === null ? "not_answered" : "answered";
  }
  updatePalette();
}

// Navigation
prevBtn.addEventListener("click", () => {
  saveCurrentAnswer();
  if (currentIndex > 0) {
    currentIndex--;
    if (status[currentIndex] === "not_visited") {
      status[currentIndex] = answers[currentIndex] === null ? "not_answered" : "answered";
    }
    renderQuestion(currentIndex);
  }
});

nextBtn.addEventListener("click", () => {
  saveCurrentAnswer();
  if (currentIndex < totalQuestions - 1) {
    currentIndex++;
    if (status[currentIndex] === "not_visited") {
      status[currentIndex] = answers[currentIndex] === null ? "not_answered" : "answered";
    }
    renderQuestion(currentIndex);
  }
});

reviewBtn.addEventListener("click", () => {
  if (status[currentIndex] === "review") {
    status[currentIndex] = answers[currentIndex] === null ? "not_answered" : "answered";
  } else {
    status[currentIndex] = "review";
  }
  updatePalette();
});

clearBtn.addEventListener("click", () => {
  answers[currentIndex] = null;
  document.querySelectorAll("input[name='option']").forEach(r => r.checked = false);
  status[currentIndex] = "not_answered";
  updatePalette();
});

reviewSubmitBtn.addEventListener("click", () => {
  saveCurrentAnswer();
  const ansCount    = answers.filter(a => a !== null).length;
  const reviewCount = status.filter(s => s === "review").length;
  const unAnsCount  = totalQuestions - ansCount;

  alert(
    "Review Summary:\n" +
    `Answered: ${ansCount}\n` +
    `Marked for review: ${reviewCount}\n` +
    `Not answered: ${unAnsCount}\n\n` +
    "When you are ready, click Final Submit."
  );
});

function finalSubmit(force = false) {
  saveCurrentAnswer();

  if (!force) {
    const ansCount    = answers.filter(a => a !== null).length;
    const reviewCount = status.filter(s => s === "review").length;
    const unAnsCount  = totalQuestions - ansCount;
    const ok = confirm(
      "Final Submit?\n\n" +
      `Answered: ${ansCount}\n` +
      `Marked for review: ${reviewCount}\n` +
      `Not answered: ${unAnsCount}\n\n` +
      "You won't be able to change answers after this."
    );
    if (!ok) return;
  }

  const payload = questions.map((q, i) => ({
    qid: q.qid,
    qno: q.qno,
    index: i + 1,
    selected: answers[i],   // 0–3 or null
    status: status[i],
  }));

  answersInput.value = JSON.stringify(payload);
  formEl.submit(); // POST to submit_exam.php
}

submitBtn.addEventListener("click", () => finalSubmit(false));

// Timer
let secondsLeft = <?php echo max(1, (int)$diffSec); ?>;

const timerInterval = setInterval(() => {
  if (secondsLeft <= 0) {
    timeEl.textContent = "00:00";
    clearInterval(timerInterval);
    durationOverInput.value = "1";
    alert("Time is over. Your exam will now be submitted.");
    finalSubmit(true);
    return;
  }

  const m = Math.floor(secondsLeft / 60);
  const s = secondsLeft % 60;
  timeEl.textContent = `${String(m).padStart(2,"0")}:${String(s).padStart(2,"0")}`;
  secondsLeft--;
}, 1000);

// Calculator
const calcDisplay = document.getElementById("calcDisplay");
document.querySelectorAll(".calc-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    const val = btn.dataset.val;
    if (val === "C") {
      calcDisplay.value = "";
    } else if (val === "CE") {
      calcDisplay.value = calcDisplay.value.slice(0, -1);
    } else if (val === "=") {
      try {
        if (!/^[0-9+\-*/.() ]+$/.test(calcDisplay.value)) throw new Error("Invalid");
        // eslint-disable-next-line no-eval
        const result = eval(calcDisplay.value);
        calcDisplay.value = result;
      } catch (e) {
        calcDisplay.value = "Error";
      }
    } else {
      calcDisplay.value += val;
    }
  });
});

// Init
buildPalette();
status[0] = "not_answered";
renderQuestion(0);
</script>

<?php
include_once __DIR__ . '/../footer.php';
?>
