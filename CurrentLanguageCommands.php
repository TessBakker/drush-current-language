<?php

declare(strict_types=1);

namespace Drush\Commands\drush_current_language;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drush\Boot\BootstrapManager;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class CurrentLanguageCommands extends DrushCommands
{
  use AutowireTrait;

  public function __construct(
    protected BootstrapManager $bootstrapManager,
    protected LanguageManagerInterface $languageManager,
    protected LanguageNegotiatorInterface $languageNegotiator,
    protected TranslationManager $translationManager,
    protected AccountProxyInterface $currentUser
  ) { }

  public function getBootstrapManager(): BootstrapManager {
    return $this->bootstrapManager;
  }

  public function getLanguageManager(): LanguageManagerInterface {
    return $this->languageManager;
  }

  public function getLanguageNegotiator(): LanguageNegotiatorInterface {
    return $this->languageNegotiator;
  }

  public function getTranslationManager(): TranslationManager {
    return $this->translationManager;
  }

  public function getCurrentUser(): AccountProxyInterface {
    return $this->currentUser;
  }

  /**
   * Ensure current language is set.
   */
  #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: '*')]
  public function preCommand(CommandData $commandData) {
    // Drupal must be fully bootstrapped in order to use this validation.
    if (!$this->getBootstrapManager()->hasBootstrapped( DrupalBootLevels::FULL)) {
      return;
    }
    try {
      $languageManager = $this->getLanguageManager();
      $negotiator = $this->getLanguageNegotiator();
    }
    catch (ServiceNotFoundException $exception) {
      // If we do not have these services, this command is not really useful.
      return;
    }
    $negotiator->setCurrentUser($this->getCurrentUser());

    if ($languageManager instanceof ConfigurableLanguageManagerInterface) {
      $languageManager->setNegotiator($negotiator);
      $languageManager->setConfigOverrideLanguage($languageManager->getCurrentLanguage());
    }
    $this->getTranslationManager()->setDefaultLangcode($languageManager->getCurrentLanguage()->getId());
  }

}
