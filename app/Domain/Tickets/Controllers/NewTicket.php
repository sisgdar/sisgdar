<?php

namespace Leantime\Domain\Tickets\Controllers {

    use Illuminate\Contracts\Container\BindingResolutionException;
    use Leantime\Core\Controller;
    use Leantime\Domain\Auth\Models\Roles;
    use Leantime\Domain\Projects\Services\Projects as ProjectService;
    use Leantime\Domain\Tickets\Services\Tickets as TicketService;
    use Leantime\Domain\Sprints\Services\Sprints as SprintService;
    use Leantime\Domain\Files\Services\Files as FileService;
    use Leantime\Domain\Comments\Services\Comments as CommentService;
    use Leantime\Domain\Timesheets\Services\Timesheets as TimesheetService;
    use Leantime\Domain\Users\Services\Users as UserService;
    use Leantime\Domain\Tickets\Models\Tickets as TicketModel;
    use Leantime\Domain\Auth\Services\Auth;
    use Symfony\Component\HttpFoundation\Response;
    use Leantime\Core\Frontcontroller;

    /**
     *
     */
    class NewTicket extends Controller
    {
        private ProjectService $projectService;
        private TicketService $ticketService;
        private SprintService $sprintService;
        private FileService $fileService;
        private CommentService $commentService;
        private TimesheetService $timesheetService;
        private UserService $userService;

        /**
         * @param ProjectService   $projectService
         * @param TicketService    $ticketService
         * @param SprintService    $sprintService
         * @param FileService      $fileService
         * @param CommentService   $commentService
         * @param TimesheetService $timesheetService
         * @param UserService      $userService
         * @return void
         */
        public function init(
            ProjectService $projectService,
            TicketService $ticketService,
            SprintService $sprintService,
            FileService $fileService,
            CommentService $commentService,
            TimesheetService $timesheetService,
            UserService $userService
        ): void {
            Auth::authOrRedirect([Roles::$owner, Roles::$admin, Roles::$manager, Roles::$editor]);

            $this->projectService = $projectService;
            $this->ticketService = $ticketService;
            $this->sprintService = $sprintService;
            $this->fileService = $fileService;
            $this->commentService = $commentService;
            $this->timesheetService = $timesheetService;
            $this->userService = $userService;

            if (!isset($_SESSION['lastPage'])) {
                $_SESSION['lastPage'] = BASE_URL . "/tickets/showKanban/";
            }
        }


        /**
         * @return Response
         * @throws BindingResolutionException
         */
        public function get(): Response
        {
            $ticket = app()->make(TicketModel::class, [
            "values" =>
                [
                    "userLastname" => $_SESSION['userdata']["name"],
                    "status" => 3,
                    "projectId" => $_SESSION['currentProject'],
                    "sprint" => $_SESSION['currentSprint'] ?? '',
                    "editorId" => $_SESSION['userdata']['id']
                ],
            ]);

            $ticket->date =  format(date("Y-m-d H:i:s"))->date();

            $this->tpl->assign('ticket', $ticket);
            $this->tpl->assign('ticketParents', $this->ticketService->getAllPossibleParents($ticket));
            $this->tpl->assign('statusLabels', $this->ticketService->getStatusLabels());
            $this->tpl->assign('ticketTypes', $this->ticketService->getTicketTypes());
            $this->tpl->assign('efforts', $this->ticketService->getEffortLabels());
            $this->tpl->assign('priorities', $this->ticketService->getPriorityLabels());

            $allProjectMilestones = $this->ticketService->getAllMilestones(["sprint" => '', "type" => "milestone", "currentProject" => $_SESSION["currentProject"]]);
            $this->tpl->assign('milestones', $allProjectMilestones);
            $this->tpl->assign('sprints', $this->sprintService->getAllSprints($_SESSION["currentProject"]));

            $this->tpl->assign('kind', $this->timesheetService->getLoggableHourTypes());
            $this->tpl->assign('ticketHours', 0);
            $this->tpl->assign('userHours', 0);

            $this->tpl->assign('timesheetsAllHours', 0);
            $this->tpl->assign('remainingHours', 0);

            $this->tpl->assign('userInfo', $this->userService->getUser($_SESSION['userdata']['id']));
            $this->tpl->assign('users', $this->projectService->getUsersAssignedToProject($_SESSION["currentProject"]));

            $allAssignedprojects = $this->projectService->getProjectsAssignedToUser($_SESSION['userdata']['id'], 'open');
            $this->tpl->assign('allAssignedprojects', $allAssignedprojects);

            return $this->tpl->displayPartial('tickets.newTicketModal');
        }

        /**
         * @param $params
         * @return Response
         * @throws BindingResolutionException
         */
        public function post($params): Response
        {
            if (isset($params['saveTicket']) || isset($params['saveAndCloseTicket'])) {

                $params['timeToFinish'] = format($params['timeToFinish'] ?? '')->time24toLocalTime(ignoreTimezone: true);
                $params['timeFrom'] = format($params['timeFrom'] ?? '')->time24toLocalTime(ignoreTimezone: true);
                $params['timeTo'] = format($params['timeTo'] ?? '')->time24toLocalTime(ignoreTimezone: true);

                $result = $this->ticketService->addTicket($params);

                if (is_array($result) === false) {
                    $this->tpl->setNotification($this->language->__("notifications.ticket_saved"), "success");

                    if (isset($params["saveAndCloseTicket"]) === true && $params["saveAndCloseTicket"] == 1) {
                        return Frontcontroller::redirect(BASE_URL . "/tickets/showTicket/" . $result . "?closeModal=1");
                    } else {
                        return Frontcontroller::redirect(BASE_URL . "/tickets/showTicket/" . $result);
                    }
                } else {
                    $this->tpl->setNotification($this->language->__($result["msg"]), "error");

                    $ticket = app()->make(TicketModel::class, [$params]);
                    $ticket->userLastname = $_SESSION['userdata']["name"];

                    $this->tpl->assign('ticket', $ticket);
                    $this->tpl->assign('statusLabels', $this->ticketService->getStatusLabels());
                    $this->tpl->assign('ticketTypes', $this->ticketService->getTicketTypes());
                    $this->tpl->assign('efforts', $this->ticketService->getEffortLabels());
                    $this->tpl->assign('priorities', $this->ticketService->getPriorityLabels());
                    $this->tpl->assign('milestones', $this->ticketService->getAllMilestones(["sprint" => '', "type" => "milestone", "currentProject" => $_SESSION["currentProject"]]));
                    $this->tpl->assign('sprints', $this->sprintService->getAllSprints($_SESSION["currentProject"]));

                    $this->tpl->assign('kind', $this->timesheetService->getLoggableHourTypes());
                    $this->tpl->assign('ticketHours', 0);
                    $this->tpl->assign('userHours', 0);

                    $this->tpl->assign('timesheetsAllHours', 0);
                    $this->tpl->assign('remainingHours', 0);

                    $this->tpl->assign('userInfo', $this->userService->getUser($_SESSION['userdata']['id']));
                    $this->tpl->assign('users', $this->projectService->getUsersAssignedToProject($_SESSION["currentProject"]));

                    return $this->tpl->displayPartial('tickets.newTicketModal');
                }
            }
        }
    }

}
