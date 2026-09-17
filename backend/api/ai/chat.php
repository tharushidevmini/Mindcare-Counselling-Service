<?php
// FILE: backend/api/ai/chat.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

$uid = $_SESSION['user_id'];

$input    = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? [];

if (empty($messages)) { echo json_encode(['error' => 'No messages']); exit; }

$lastUserMsg = end($messages);
if ($lastUserMsg && $lastUserMsg['role'] === 'user') {
    $stmt = $pdo->prepare("INSERT INTO ai_chat_messages (user_id, role, content) VALUES (?, 'user', ?)");
    $stmt->execute([$uid, $lastUserMsg['content']]);
}

function getLocalFallbackReply($lastText) {
    $text = strtolower($lastText);
    if (preg_match('/(suicide|kill myself|end my life|self harm|hurt myself|want to die)/i', $text)) {
        return "I can hear how much pain you're in right now, and I want you to know that your life has immense value and you don't have to carry this alone. 💚\n\nPlease reach out immediately to someone who can help:\n• **Sumithrayo Helpline**: 011-2692909 / 011-2682535 (Free, 24/7 confidential emotional support in Sri Lanka)\n• **CCCline**: 1333 (Toll-free crisis lifeline)\n• **National Mental Health Helpline**: 1926\n\nPlease reach out to one of these lifelines or an LNBTI campus counselor right now. There are people who truly care and want to listen.";
    }
    if (preg_match('/(exam|study|grades|test|assignment|deadline|gpa|marks)/i', $text)) {
        return "Academic pressure and exams can feel really overwhelming, but remember that your grades do not define your worth as a person. 📚✨\n\nTry taking a gentle 5-minute break. Break your study materials into small, bite-sized tasks using the Pomodoro technique (25 minutes focus, 5 minutes rest). Remember to drink some water and get enough sleep tonight. If you feel stuck, booking a session with an LNBTI counselor can give you personalized study-stress strategies!";
    }
    if (preg_match('/(anxious|anxiety|panic|nervous|stress|worried|overwhelmed|scared)/i', $text)) {
        return "I hear you, and it is completely understandable to feel stressed or anxious right now. Take a deep breath with me: inhale slowly for 4 seconds, hold for 4, and exhale gently for 6. 🌿\n\nGrounding technique: Notice 3 things you can see around you right now, 2 things you can touch, and 1 sound you can hear. You are safe in this moment, and you can take things one step at a time.";
    }
    if (preg_match('/(sad|depressed|lonely|alone|crying|hopeless|empty|numb)/i', $text)) {
        return "Thank you for sharing how you feel with me. It takes real courage to open up when you're feeling down or lonely. 🫂\n\nPlease be kind and patient with yourself today. Even doing something tiny — like drinking a glass of water, resting, or listening to music from our Audio Library — is a win. Remember, our campus counselors are always here if you'd like someone caring to talk to in person or online.";
    }
    if (preg_match('/(sleep|tired|insomnia|exhausted|cant sleep|can\'t sleep)/i', $text)) {
        return "Sleep troubles can make everything else feel much heavier. 🌙 Try dimming the lights, stepping away from screens 30 minutes before bed, and doing some slow, relaxing breathing. You can also explore the calming audio tracks in our MindCare Audio Library to help ease your mind into rest.";
    }
    return "Thank you for sharing that with me. I'm listening, and I want to support you through whatever you're experiencing today. 💚\n\nTake a gentle breath. Remember that you don't have to navigate challenging days on your own — whether by writing your thoughts in your private Mood Diary or booking a friendly, confidential session with our LNBTI campus counselors.";
}

$isPlaceholderKey = (!defined('GROQ_API_KEY') || GROQ_API_KEY === 'YOUR_GROQ_API_KEY_HERE' || empty(GROQ_API_KEY));

if ($isPlaceholderKey) {
    $lastContent = $lastUserMsg['content'] ?? '';
    $replyText = getLocalFallbackReply($lastContent);
    $stmt = $pdo->prepare("INSERT INTO ai_chat_messages (user_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt->execute([$uid, $replyText]);
    echo json_encode(['reply' => $replyText, 'ai_unavailable' => false]);
    exit;
}

$systemPrompt = "You are MindCare AI Counselor, a warm, empathetic mental health support assistant for LNBTI campus students in Sri Lanka.

Your role:
- Listen actively and validate feelings
- Provide evidence-based coping strategies
- Be supportive, non-judgmental, and caring
- Use simple, friendly language
- Keep responses concise (2-4 paragraphs max)
- Occasionally suggest professional counseling for serious issues
- Be culturally sensitive to Sri Lankan students

You help with: exam stress, anxiety, depression, relationships, loneliness, sleep issues, family problems, academic pressure.

IMPORTANT: If user mentions suicide, self-harm, or crisis situations:
1. Acknowledge their pain with compassion
2. Encourage them to call Sumithrayo: 011-2692909 or CCCline: 1333
3. Remind them that help is available

Never diagnose medical conditions. Always recommend professional help for serious issues.
Start responses with empathy. Use occasional emojis to feel warm and approachable.";

$groqMessages = array_merge(
    [['role' => 'system', 'content' => $systemPrompt]],
    $messages
);

$payload = [
    'model'      => 'llama-3.3-70b-versatile',
    'messages'   => $groqMessages,
    'max_tokens' => 700,
];

function callGroq($payload) {
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$response, $httpCode];
}

[$response, $httpCode] = callGroq($payload);

$retries = 0;
while ($httpCode === 429 && $retries < 1) {
    sleep(2);
    [$response, $httpCode] = callGroq($payload);
    $retries++;
}

$aiUnavailable = false;
$replyText = getLocalFallbackReply($lastUserMsg['content'] ?? '');

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $replyText = $data['choices'][0]['message']['content'] ?? $replyText;
} else {
    $aiUnavailable = true;
    error_log("Groq API error [$httpCode]: $response");
}

$stmt = $pdo->prepare("INSERT INTO ai_chat_messages (user_id, role, content) VALUES (?, 'assistant', ?)");
$stmt->execute([$uid, $replyText]);

echo json_encode(['reply' => $replyText, 'ai_unavailable' => $aiUnavailable]);
?>