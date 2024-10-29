<?php

namespace AC_SYNC\Includes\Classes\Utils;

if (!defined('ABSPATH'))
  exit; // Exit if accessed directly

class Action_Wear_Circuit_Breaker
{
  private $isOpen = false;
  private $failures = 0;
  private $lastTry = 0;
  public const MAX_FAILURES = 3;
  public const RETRY_TIMEOUT = 120;

  public const CACHE_KEY = "_ACTIONWEAR_CIRCUIT_FAIL";
  public const RETRY_KEY = "_ACTIONWEAR_CIRCUIT_RETRY";

  public function __construct(int $failures, int $lastTry)
  {
    $this->failures = $failures;
    $this->lastTry = $lastTry;
    $this->isOpen = $this->failures >= self::MAX_FAILURES;
  }

  public function updateLastTry()
  {
    $this->lastTry = time();
    update_option(self::RETRY_KEY, $this->lastTry);
  }

  public function incrementFailures()
  {
    $this->failures++;
    update_option(self::CACHE_KEY, $this->failures);
  }

  public function resetFailures()
  {
    $this->failures = 0;
    update_option(self::CACHE_KEY, $this->failures);
  }

  public function allowRequest(): bool
  {
    // reset failures if last try was more than RETRY_TIMEOUT seconds ago and close the circuit
    if (time() - $this->lastTry > self::RETRY_TIMEOUT) {
      $this->resetFailures();
      $this->isOpen = false;
    }
    if ($this->isOpen) {
      return false;
    }

    return true;
  }

  public function handleSuccess()
  {
    $this->resetFailures();
    $this->isOpen = false;
  }

  public function handleFailure()
  {
    $this->incrementFailures();
    $this->updateLastTry();
    if ($this->failures >= self::MAX_FAILURES) {
      $this->isOpen = true;
    }
  }

  /**
   * @return int
   */
  public function getFailures(): int
  {
    return $this->failures;
  }

  /**
   * @return bool
   */
  public function getIsOpen(): bool
  {
    return $this->isOpen;
  }

  /**
   * @return int
   */
  public function getLastTry(): int
  {
    return $this->lastTry;
  }
}