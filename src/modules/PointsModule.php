<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\AccessDeniedException;
use app\exceptions\NotFoundException;
use app\model\repository\PointsRepository;
use MDataCheck;
use models\MLinks;

class PointsModule implements ModuleInterface
{
    private MyDB $db;

    private WebUser $webUser;

    private ?PointsRepository $pointsRepository = null;

    public function __construct(MyDB $db, WebUser $webUser)
    {
        $this->db = $db;
        $this->webUser = $webUser;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        if (!$request->isAjax()) {
            throw new NotFoundException();
        }

        if ($request->isPost() && $request->getLevel1() !== null) {
            $pointId = (int) $request->getLevel1();

            if ($pointId === 0) {
                throw new NotFoundException();
            }

            if ($request->getLevel2() === 'contacts') {
                if (!$this->webUser->isEditor()) {
                    throw new AccessDeniedException();
                }
                $this->saveContacts($pointId, $request, $response);
            }
        }

        throw new NotFoundException();
    }

    /**
     * @param int $pointId
     * @param SiteRequest $request
     * @param SiteResponse $response
     * @throws NotFoundException
     */
    private function saveContacts(int $pointId, SiteRequest $request,  SiteResponse $response): void
    {
        $repository = $this->getPointsRepository();
        $point = $repository->getItemByPk($pointId);

        if ($point === null) {
            throw new NotFoundException();
        }

        $address = $request->getPostParameter('address');
        if ($address !== null) {
            $point->pt_adress = $address;
        }
        $website = $request->getPostParameter('website');
        if ($website !== null) {
            $point->pt_website = $website;
        }
        $phone = $request->getPostParameter('phone');
        if ($phone !== null) {
            $point->pt_phone = $phone;
        }
        $worktime = $request->getPostParameter('worktime');
        if ($worktime !== null) {
            $point->pt_worktime = $worktime;
        }

        $point->pt_lastup_user = $this->webUser->getId();
        $point->pt_lastup_date = $point->now();

        $repository->save($point);
        $this->resetCheckerQueue($pointId);

        $point = $repository->getItemByPk($pointId);

        $response->getContent()->setJson(['id' => $point->getId()]);
    }

    /**
     * @param int $id
     */
    private function resetCheckerQueue(int $id): void
    {
        $mDataCheck = new MDataCheck($this->db);
        $mDataCheck->deleteChecked(MDataCheck::ENTITY_POINTS, $id);

        $linksModel = new MLinks($this->db);
        $linksModel->deleteByPoint($id);
    }

    private function getPointsRepository(): PointsRepository
    {
        if ($this->pointsRepository === null) {
            $this->pointsRepository = new PointsRepository($this->db);
        }

        return $this->pointsRepository;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === 'point';
    }
}
