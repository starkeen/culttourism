<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\exceptions\NotFoundException;
use app\includes\ReCaptcha;
use GuzzleHttp\Client;
use Mailing;
use MCandidatePoints;
use MFeedback;

class FeedbackModule extends Module implements ModuleInterface
{
    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel1() === null) {
            $this->getCommon($request, $response);
        } elseif ($request->getLevel1() === 'newpoint') {
            $this->getAdd($response);
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'feedback';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }


    /**
     * Обработка формы добавления точки
     * @param SiteResponse $response
     */
    private function getAdd(SiteResponse $response): void
    {
        $cp = new MCandidatePoints($this->db);
        if (!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] === null) {
            $_SESSION['feedback_referer'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        }
        if (isset($_POST) && !empty($_POST)) {
            $spamStatusOK = $this->getReCaptcha()->check($_POST['g-recaptcha-response'] ?? null);

            $loggedSender = $_SESSION['user_id'] ?? null;
            $isAdminSender = $loggedSender !== null && (int) $loggedSender !== 0;

            $cp->add(
                [
                    'cp_title' => $_POST['title'],
                    'cp_city' => $_POST['region'],
                    'cp_text' => $_POST['descr'],
                    'cp_addr' => $_POST['addrs'] ?? '',
                    'cp_phone' => $_POST['phone'],
                    'cp_web' => $_POST['web'],
                    'cp_worktime' => $_POST['worktime'],
                    'cp_referer' => $_SESSION['feedback_referer'],
                    'cp_sender' => $_POST['name'] . ' <' . $_POST['email'] . '>',
                    'cp_source_id' => MCandidatePoints::SOURCE_FORM,
                    'cp_state' => $spamStatusOK === true ? MCandidatePoints::STATUS_NEW : MCandidatePoints::STATUS_SPAM,
                    'cp_active' => $spamStatusOK === true ? 1 : 0,
                ]
            );

            if ($spamStatusOK === true && $isAdminSender !== true) {
                $mailAttrs = [
                    'user_name' => $_POST['name'],
                    'user_mail' => $_POST['email'],
                    'add_city' => $_POST['region'],
                    'add_title' => $_POST['title'],
                    'add_text' => $_POST['descr'],
                    'add_contacts' => $_POST['addrs']
                        . ' ' . $_POST['phone']
                        . ' ' . $_POST['web']
                        . ' ' . $_POST['worktime'],
                    'referer' => $_SESSION['feedback_referer']
                ];

                Mailing::sendLetterCommon($this->globalConfig->getMailFeedback(), 5, $mailAttrs);
            }

            $response->getContent()->setBody($this->getAddingSuccess($_POST['title'], $_POST['descr'], $_POST['region']));
            unset($_POST);
        } else {
            $response->getContent()->setBody($this->getAddingForm($response));
        }
    }

    /**
     * @param SiteRequest $request
     * @param SiteResponse $response
     */
    private function getCommon(SiteRequest $request, SiteResponse $response): void
    {
        $data = [
            'error' => null,
            'success' => null,
            'fname' => null,
            'fsurname' => null,
            'ftext' => null,
            'fmail' => null,
        ];

        if ($request->isPost()) {
            $this->processFeedbackPosting($request, $response);
        } else {
            $response->getContent()->setBody($this->getCommonForm($data));
        }
    }

    /**
     * Обработка запроса на сохранение обратной связи
     * @param SiteRequest $request
     * @param SiteResponse $response
     */
    private function processFeedbackPosting(SiteRequest $request, SiteResponse $response): void
    {
       if ((!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] === null) && $request->getReferer() !== null) {
            $_SESSION['feedback_referer'] = $request->getReferer();
        }
        $referer = !empty($_SESSION['feedback_referer']) ? $_SESSION['feedback_referer'] : null;

        $data['fname'] = cut_trash_text($_POST['fname']);
        $data['fsurname'] = $_POST['fsurname'] ?? null;
        $data['fmail'] = cut_trash_text($_POST['fmail']);
        $data['ftext'] = cut_trash_text($_POST['ftext']);

        if ($data['ftext'] === '') {
            $data['error'] = 'ftext';
        }
        if ($data['fname'] === '') {
            $data['error'] = 'fname';
        }

        if (!isset($data['error'])) {
            $data['success'] = true;
            $fb = new MFeedback($this->db);
            $fb->add(
                [
                    'fb_name' => $data['fname'],
                    'fb_text' => $data['ftext'],
                    'fb_sendermail' => $data['fmail'],
                    'fb_referer' => $referer,
                    'fb_ip' => $_SERVER['REMOTE_ADDR'],
                    'fb_browser' => $_SERVER['HTTP_USER_AGENT'],
                ]
            );
            $spamStatusOK = $this->getReCaptcha()->check($_POST['g-recaptcha-response'] ?? null);
            if ($spamStatusOK) {
                $mailAttributes = [
                    'user_name' => $data['fname'],
                    'user_mail' => $data['fmail'],
                    'feed_text' => $data['ftext'],
                    'referer' => $referer,
                ];
                Mailing::sendLetterCommon($this->globalConfig->getMailFeedback(), 4, $mailAttributes);
            }
            $response->getContent()->setBody($this->getCommonSuccess($data));
        } else {
            $response->getContent()->setBody($this->getCommonForm($data));
        }
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function getCommonForm(array $data): string
    {
        $data['recaptcha_key'] = ReCaptcha::KEY;
        return $this->templateEngine->getContent('feedback/feedback_form_page.tpl', $data);
    }

    /**
     * @param array $data
     * @return string
     */
    private function getCommonSuccess(array $data): string
    {
        return $this->templateEngine->getContent('feedback/feedsuccess.tpl', $data);
    }

    /**
     * @param SiteResponse $response
     * @return string
     */
    private function getAddingForm(SiteResponse $response): string
    {
        $response->getContent()->getHead()->addTitleElement('Добавить объект (музей, гостиницу, кафе и др.)');

        return $this->templateEngine->getContent('feedback/point_add_form_page.tpl', [
            'recaptcha_key' => ReCaptcha::KEY,
        ]);
    }

    /**
     * @param $title
     * @param $descr
     * @param $region
     * @return string
     */
    private function getAddingSuccess($title, $descr, $region): string
    {
        $this->templateEngine->assign('add_title', $title);
        $this->templateEngine->assign('add_descr', nl2br($descr));
        $this->templateEngine->assign('add_region', $region);
        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/feedback/addsuccess.tpl');
    }

    /**
     * @return ReCaptcha
     */
    private function getReCaptcha(): ReCaptcha
    {
        $httpClient = new Client();
        return new ReCaptcha($httpClient);
    }
}
