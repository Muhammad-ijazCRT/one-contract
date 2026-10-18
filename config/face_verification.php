<?php

return [
    'session_ttl_minutes' => (int) env('FACE_VERIFICATION_SESSION_TTL', 10),
    'min_liveness_seconds' => (int) env('FACE_VERIFICATION_MIN_SECONDS', 4),
    'face_match_threshold' => (int) env('FACE_VERIFICATION_MATCH_THRESHOLD', 22),
    'id_face_match_threshold' => (int) env('FACE_VERIFICATION_ID_MATCH_THRESHOLD', 28),
    'id_face_match_soft_threshold' => (int) env('FACE_VERIFICATION_ID_MATCH_SOFT', 34),
    'challenges' => ['center', 'blink', 'nod', 'shake'],
    'challenges_per_session' => 3,
];
