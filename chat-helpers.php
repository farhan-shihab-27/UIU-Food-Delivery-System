<?php

function render_chat_messages($messages, $myUserId, $myAvatar = '', $otherAvatar = '') {
    if (empty($messages)) {
        echo "<div class='chat-date-divider'>No messages yet — say hi!</div>";
        return;
    }

    /* Fallbacks if nothing provided */
    if ($myAvatar === '') {
        $myAvatar = 'https://images.unsplash.com/photo-1607746882042-944635dfe10e?w=80&q=80&auto=format&fit=crop';
    }
    if ($otherAvatar === '') {
        $otherAvatar = 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&q=80&fit=crop';
    }

    foreach ($messages as $m) {
        $sent = ((int)$m['sender_id'] === (int)$myUserId);
        $cls  = $sent ? 'sent' : 'received';
        $avatar = $sent ? $myAvatar : $otherAvatar;

        echo "<div class='chat-msg-row {$cls}'>";
        echo "<img src='" . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') . "' alt='User' class='chat-msg-avatar' />";
        echo "<div>";
        echo "<div class='chat-bubble {$cls}'>" . htmlspecialchars($m['content'], ENT_QUOTES, 'UTF-8') . "</div>";
        echo "<div class='chat-bubble-time' style='" . ($sent ? 'text-align:right;' : '') . "'>"
           . date('g:i A', strtotime($m['created_at'])) . "</div>";
        echo "</div>";
        echo "</div>";
    }
}