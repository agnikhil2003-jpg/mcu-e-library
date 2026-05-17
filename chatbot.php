<?php
// ============================================================
// chatbot.php — AI Chatbot (powered by Anthropic Claude API)
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'AI Library Assistant';

// Fetch all book titles/authors for context
$booksList = $pdo->query("SELECT title, author, category, (total_quantity-issued_quantity) AS avail FROM books ORDER BY title")->fetchAll();
$booksContext = implode("\n", array_map(fn($b) => "- {$b['title']} by {$b['author']} [{$b['category']}] — " . ($b['avail'] > 0 ? "{$b['avail']} available" : "Out of Stock"), $booksList));

// ── AJAX Request ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $userMsg = trim($_POST['message'] ?? '');
    if (empty($userMsg)) { echo json_encode(['reply' => 'Please type a message.']); exit; }

    $systemPrompt = "You are a helpful AI library assistant for MCU E-Library (Makhanlal Chaturvedi National University, Bhopal). 
You help students find books, answer questions about library policies, and assist with academic queries.

Library Rules:
- Loan period: 14 days
- Fine: ₹2 per day after due date
- Students must show ID to collect issued books
- Maximum 2 books can be issued at a time

Current Library Collection (excerpt):
$booksContext

You can answer questions about:
- Book availability and location
- Library policies and fines
- Academic subjects and topics
- Book recommendations for various courses
- Study tips and resources

Be friendly, concise, and helpful. If asked about a specific book not in the list, say you'll check and suggest they search on the website.";

    $apiKey = ANTHROPIC_API_KEY;

    if ($apiKey === 'YOUR_ANTHROPIC_API_KEY_HERE') {
        // Demo mode — rule-based responses
        $reply = getRuleBasedReply($userMsg, $booksList);
        echo json_encode(['reply' => $reply]); exit;
    }

    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 500,
        'system'     => $systemPrompt,
        'messages'   => [['role' => 'user', 'content' => $userMsg]]
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $result = curl_exec($ch);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(['reply' => 'Sorry, I could not connect to the AI service. Please try again later.']);
        exit;
    }

    $data  = json_decode($result, true);
    $reply = $data['content'][0]['text'] ?? 'I could not generate a response. Please try again.';
    echo json_encode(['reply' => $reply]);
    exit;
}

// Rule-based fallback
function getRuleBasedReply(string $msg, array $books): string {
    $msg = strtolower($msg);

    if (str_contains($msg, 'fine') || str_contains($msg, 'penalty')) {
        return "The library charges ₹2 per day for late returns. Fines start the day after your due date. You can pay fines at the library desk. 📚";
    }
    if (str_contains($msg, 'how long') || str_contains($msg, 'loan') || str_contains($msg, 'borrow period')) {
        return "The standard loan period is **14 days**. You can issue up to 2 books at a time. Please return books on time to avoid fines!";
    }
    if (str_contains($msg, 'available') || str_contains($msg, 'stock')) {
        $avail = array_filter($books, fn($b) => $b['avail'] > 0);
        return "Currently, " . count($avail) . " books are available. Browse our collection at /books.php or search by title/author. 🔍";
    }
    if (str_contains($msg, 'computer') || str_contains($msg, 'programming')) {
        $cs = array_filter($books, fn($b) => str_contains(strtolower($b['category']), 'computer'));
        $titles = array_slice(array_map(fn($b) => "**{$b['title']}**", $cs), 0, 3);
        return "For Computer Science, I recommend: " . implode(', ', $titles) . ". Check /books.php for more! 💻";
    }
    if (str_contains($msg, 'hello') || str_contains($msg, 'hi') || str_contains($msg, 'hey')) {
        return "Hello! 👋 I'm your MCU Library AI Assistant. I can help you find books, answer questions about fines and loan periods, or suggest reading material. What would you like to know?";
    }
    if (str_contains($msg, 'recommend') || str_contains($msg, 'suggest')) {
        $random = array_slice($books, 0, 3);
        $list   = implode(', ', array_map(fn($b) => "**{$b['title']}** by {$b['author']}", $random));
        return "Here are some recommendations: $list. Visit /recommend.php for AI-powered personalized suggestions! 🌟";
    }

    return "I'm here to help with library queries! You can ask me about:\n- 📚 Book availability\n- ⏰ Loan periods and fines\n- 🔍 Book recommendations\n- 📖 Study resources\n\nOr search for books at /books.php";
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div class="chatbot-container">

    <div class="section-header" style="text-align:left;margin-bottom:1.5rem;">
      <span class="tag"><i class="fas fa-robot"></i> AI Powered</span>
      <h2>Library AI Assistant</h2>
      <p>Ask anything about books, subjects, library policies, or get study recommendations.</p>
    </div>

    <div class="chat-window">
      <div class="chat-header">
        <div class="chat-bot-avatar"><i class="fas fa-robot"></i></div>
        <div class="chat-header-text">
          <h3>MCU Library Assistant</h3>
          <p>Powered by Claude AI · Always ready to help</p>
        </div>
        <div class="chat-status">Online</div>
      </div>

      <!-- Suggestions -->
      <div class="chat-suggestions">
        <span class="suggestion-pill">📚 What books are available?</span>
        <span class="suggestion-pill">⏰ What is the loan period?</span>
        <span class="suggestion-pill">💰 How are fines calculated?</span>
        <span class="suggestion-pill">💻 Recommend CS books</span>
        <span class="suggestion-pill">🤖 Recommend AI books</span>
        <span class="suggestion-pill">📊 Best books for MBA</span>
      </div>

      <!-- Messages -->
      <div class="chat-messages" id="chatMessages">
        <div class="message bot">
          <div class="msg-avatar"><i class="fas fa-robot"></i></div>
          <div class="msg-bubble">
            Hello! 👋 I'm your <strong>MCU Library AI Assistant</strong>.<br><br>
            I can help you:<br>
            • Find and check book availability<br>
            • Understand library policies & fines<br>
            • Get personalized book recommendations<br>
            • Answer academic subject questions<br><br>
            What can I help you with today?
          </div>
        </div>
      </div>

      <!-- Input -->
      <form class="chat-input-area" id="chatForm">
        <input type="text" id="chatInput" class="form-control"
               placeholder="Type your message…" autocomplete="off" maxlength="500">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-paper-plane"></i>
        </button>
      </form>
    </div>

    <!-- Info Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:2rem;">
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;text-align:center;">
        <div style="font-size:1.8rem;margin-bottom:.5rem;">📚</div>
        <h4 style="font-size:.95rem;margin-bottom:.3rem;">Book Search</h4>
        <p style="font-size:.82rem;">Ask me to find books by title, author, or subject area.</p>
      </div>
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;text-align:center;">
        <div style="font-size:1.8rem;margin-bottom:.5rem;">⏰</div>
        <h4 style="font-size:.95rem;margin-bottom:.3rem;">Library Policies</h4>
        <p style="font-size:.82rem;">Get answers about fines, loan periods, and rules.</p>
      </div>
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;text-align:center;">
        <div style="font-size:1.8rem;margin-bottom:.5rem;">🌟</div>
        <h4 style="font-size:.95rem;margin-bottom:.3rem;">Recommendations</h4>
        <p style="font-size:.82rem;">Get smart book suggestions for your courses.</p>
      </div>
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;text-align:center;">
        <div style="font-size:1.8rem;margin-bottom:.5rem;">🎓</div>
        <h4 style="font-size:.95rem;margin-bottom:.3rem;">Study Help</h4>
        <p style="font-size:.82rem;">Ask about academic topics and get resource suggestions.</p>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
