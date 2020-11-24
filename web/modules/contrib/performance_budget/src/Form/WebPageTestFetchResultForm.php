<?php

namespace Drupal\performance_budget\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\performance_budget\Exception\WebPageTestApiErrorException;
use Drupal\performance_budget\Exception\WebPageTestApiPendingException;
use Drupal\web_page_archive\Plugin\CaptureUtilityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Downloads a web page archive run.
 */
class WebPageTestFetchResultForm extends FormBase {

  /**
   * Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Capture utility manager service.
   *
   * @var \Drupal\web_page_archive\Plugin\CaptureUtilityManager
   */
  protected $captureUtilityManager;

  /**
   * Constructs a new DownloadRunForm.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Lock service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\web_page_archive\Plugin\CaptureUtilityManager $capture_utility_manager
   *   Capture utility manager service.
   */
  public function __construct(LockBackendInterface $lock, LoggerChannelInterface $logger, MessengerInterface $messenger, CaptureUtilityManager $capture_utility_manager) {
    $this->lock = $lock;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->captureUtilityManager = $capture_utility_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lock'),
      $container->get('logger.factory')->get('performance_budget'),
      $container->get('messenger'),
      $container->get('plugin.manager.capture_utility')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wpt_fetch_result';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $web_page_archive_run_revision = NULL) {
    $this->runRevision = $web_page_archive_run_revision;

    $wpa = $this->runRevision->getConfigEntity();
    $use_cron = $wpa->getUseCron();

    if (!$use_cron && $wpa->hasCaptureUtilityInstance('pb_wpt_capture')) {
      $form['intro'] = [
        '#prefix' => '<div class="fetch-result-intro">',
        '#markup' => $this->t('This run may have pending results available from WebPageTest. Click "Fetch Results" to attempt to download the test results.'),
        '#suffix' => '</div>',
      ];

      $form['button'] = [
        '#prefix' => '<div class="fetch-results-button">',
        '#type' => 'submit',
        '#value' => $this->t('Fetch Results'),
        '#suffix' => '</div>',
      ];
    }
    elseif ($use_cron) {
      $form['intro'] = [
        '#markup' => $this->t('This capture job uses cron scheduling, so manual result downloading is disabled.'),
      ];
    }
    else {
      $form['intro'] = [
        '#markup' => $this->t('This capture job results could not be loaded. Please try again later.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitted = TRUE;
    $wpa = $this->runRevision->getConfigEntity();
    $redirect = TRUE;
    $use_cron = $wpa->getUseCron();

    if (!$use_cron && $wpa->hasCaptureUtilityInstance('pb_wpt_capture')) {
      $id = $wpa->id();

      // Attempt to acquire lock.
      $lock_id = "web_page_archive_cron:{$id}";
      if (!$this->lock->acquire($lock_id)) {
        throw new \Exception('Could not acquire lock at this time');
      }
      $config = [];
      foreach ($wpa->getCaptureUtilities()->getConfiguration() as $capture_utility) {
        if ($capture_utility['id'] == 'pb_wpt_capture') {
          $config = $capture_utility;
          break;
        }
      }

      $capture_utility = $this->captureUtilityManager->createInstance('pb_wpt_capture', $config);
      $run_uuid = $this->runRevision->getRunUuid();
      $success_ct = 0;
      foreach ($wpa->getUrlList() as $url) {
        try {
          $data = [
            'url' => $url,
            'run_uuid' => $run_uuid,
            'run_entity' => $this->runRevision,
            'web_page_archive' => $wpa,
          ];
          $capture_utility->capture($data);

          $data['capture_response'] = $capture_utility->getResponse();
          if (isset($data['capture_response'])) {
            $this->runRevision->markCaptureComplete($data);
          }
        }
        // Results are still pending.
        catch (WebPageTestApiPendingException $exception) {
          $message = $exception->getMessage();
          $this->messenger->addMessage($message);
          $redirect = FALSE;
        }
        // Results could not be retrieved.
        catch (WebPageTestApiErrorException $exception) {
          $message = $exception->getMessage();
          $this->messenger->addWarning($message);
          $this->logger->warning($message);
        }
      };

      $this->lock->release($lock_id);
    }
    else {
      $this->messenger->addWarning($this->t('Only jobs with cron-disabled and the pb_wpt_capture capture utility can fetch results.'));
    }

    if ($redirect) {
      $form_state->setRedirect('entity.web_page_archive.canonical', ['web_page_archive' => $wpa->id()]);
    }
  }

  /**
   * Generates the title for the download page.
   */
  public function title($web_page_archive_run_revision) {
    return $this->t('Fetch Results: @job', ['@job' => $web_page_archive_run_revision->label()]);
  }

}
