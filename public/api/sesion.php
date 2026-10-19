<?php
require_once __DIR__ . '/../../app/config/config.php';
auth();
responder_json(['success' => true, 'expira_en' => SESSION_TIMEOUT]);
