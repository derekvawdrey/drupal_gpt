<?php

namespace Drupal\drupal_gpt\Model;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\drupal_gpt\Controller\ApiController;

class DrupalGPTSession {

    // TODO: Change to \Drupal::config('drupal_gpt.settings')
    private static int $MAX_REQUESTS_PER_SESSION = 10;
    private static int $MAX_REQUESTS_PER_MINUTE = 10;
    private static int $CLEANUP_AFTER_MINUTES = 3;

    protected $timestamp;
    protected string $sessionId;
    protected bool $isActive;
    protected ApiController $apiController;

    

}
