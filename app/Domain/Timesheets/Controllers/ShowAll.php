<?php

namespace Leantime\Domain\Timesheets\Controllers;

use Leantime\Core\Controller;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Users\Repositories\Users as UserRepository;
use Leantime\Domain\Projects\Services\Projects as ProjectService;
use Leantime\Domain\Clients\Services\Clients as ClientService;
use Leantime\Domain\Timesheets\Services\Timesheets as TimesheetService;
use Leantime\Domain\Auth\Services\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class ShowAll extends Controller
{
    private ProjectService $projectService;
    private ClientService $clientService;
    private TimesheetService $timesheetsService;

    /**
     * init - initialize private variables
     *
     * @param ProjectService   $projectService
     * @param TimesheetService $timesheetsService
     * @param ClientService    $clientService
     *
     * @return void
     */
    public function init(
        ProjectService $projectService,
        TimesheetService $timesheetsService,
        ClientService $clientService,
    ): void {
        $this->timesheetsService = $timesheetsService;
        $this->projectService = $projectService;
        $this->clientService = $clientService;
    }

    /**
     * run - display template and edit data
     *
     * @return Response
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function run(): Response
    {
        //Only admins and employees
        Auth::authOrRedirect([Roles::$owner, Roles::$admin, Roles::$manager], true);

        $_SESSION['lastPage'] = BASE_URL . "/timesheets/showAll";

        if (isset($_POST['saveInvoice']) === true) {
            $invEmpl = [];
            $invComp = [];
            $paid = [];

            if (isset($_POST['invoicedEmpl']) === true) {
                $invEmpl = $_POST['invoicedEmpl'];
            }

            if (isset($_POST['invoicedComp']) === true) {
                $invComp = $_POST['invoicedComp'];
            }

            if (isset($_POST['paid']) === true) {
                $paid = $_POST['paid'];
            }

            $this->timesheetsService->updateInvoices($invEmpl, $invComp, $paid);
        }

        $invEmplCheck = '0';
        $invCompCheck = '0';

        $projectFilter =  "";
        $dateFromMk = mktime(0, 0, 0, date("m"), '1', date("Y"));
        $dateToMk = mktime(0, 0, 0, date("m"), date("t"), date("Y"));

        $dateFrom = date("Y-m-d", $dateFromMk);
        $dateTo = date("Y-m-d", $dateToMk);
        $kind = 'all';
        $userId = null;

        if (isset($_POST['kind']) && $_POST['kind'] != '') {
            $kind = strip_tags($_POST['kind']);
        }

        if (isset($_POST['userId']) && $_POST['userId'] != '') {
            $userId = intval(strip_tags($_POST['userId']));
        }

        if (isset($_POST['dateFrom']) && $_POST['dateFrom'] != '') {
            $dateFrom = format($_POST['dateFrom'])->isoDate();
        }

        if (isset($_POST['dateTo']) && $_POST['dateTo'] != '') {
            $dateTo = format($_POST['dateTo'])->isoDateEnd();
        }

        if (isset($_POST['invEmpl']) === true) {
            $invEmplCheck = $_POST['invEmpl'];

            if ($invEmplCheck == 'on') {
                $invEmplCheck = '1';
            } else {
                $invEmplCheck = '0';
            }
        } else {
            $invEmplCheck = '0';
        }

        if (isset($_POST['invComp']) === true) {
            $invCompCheck = ($_POST['invComp']);

            if ($invCompCheck == 'on') {
                $invCompCheck = '1';
            } else {
                $invCompCheck = '0';
            }
        }

        if (isset($_POST['paid']) === true) {
            $paidCheck = ($_POST['paid']);

            if ($paidCheck == 'on') {
                $paidCheck = '1';
            } else {
                $paidCheck = '0';
            }
        } else {
            $paidCheck = '0';
        }

        $projectFilter = "";
        if (isset($_POST['project']) && $_POST['project'] != '') {
            $projectFilter = strip_tags($_POST['project']);
        }

        $clientId = -1;
        if (isset($_POST['clientId']) && $_POST['clientId'] != '') {
            $clientId = strip_tags($_POST['clientId']);
        }

        if (isset($_POST['export'])) {
            $values = array(
                'project' => $projectFilter,
                'clientId' => $clientId,
                'kind' => $kind,
                'userId' => $userId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'invEmplCheck' => $invEmplCheck,
                'invCompCheck' => $invCompCheck,
            );
            $this->timesheetsService->export($values);
        }

        $user = app()->make(UserRepository::class);
        $employees = $user->getAll();

        $this->tpl->assign('employeeFilter', $userId);
        $this->tpl->assign('employees', $employees);
        $this->tpl->assign('dateFrom', $dateFrom);
        $this->tpl->assign('dateTo', $dateTo);

        $this->tpl->assign('actKind', $kind);
        $this->tpl->assign('kind', $this->timesheetsService->getBookedHourTypes());
        $this->tpl->assign('invComp', $invCompCheck);
        $this->tpl->assign('invEmpl', $invEmplCheck);
        $this->tpl->assign('paid', $paidCheck);
        $this->tpl->assign('allProjects', $this->projectService->getAll());
        $this->tpl->assign('projectFilter', $projectFilter);
        $this->tpl->assign('clientFilter', $clientId);
        $this->tpl->assign('allClients', $this->clientService->getAll());
        $this->tpl->assign('allTimesheets', $this->timesheetsService->getAll((int)$projectFilter, $kind, $dateFrom, $dateTo, $userId, $invEmplCheck, $invCompCheck, '-1', $paidCheck, $clientId));

        return $this->tpl->display('timesheets.showAll');
    }
}
