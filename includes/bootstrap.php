<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/CrimeReport.php';
require_once __DIR__ . '/../models/Assignment.php';
require_once __DIR__ . '/../models/CaseUpdate.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Evidence.php';
require_once __DIR__ . '/../models/OfficerCaseFile.php';
require_once __DIR__ . '/../models/Criminal.php';

ensure_case_management_schema();
