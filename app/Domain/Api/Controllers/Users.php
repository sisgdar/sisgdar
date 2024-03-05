<?php

namespace Leantime\Domain\Api\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Core\Controller;
use Leantime\Core\Environment;
use Leantime\Core\Fileupload as FileuploadCore;
use Leantime\Domain\Files\Repositories\Files as FileRepository;
use Leantime\Domain\Users\Services\Users as UserService;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class Users extends Controller
{
    private UserService $userService;
    private FileRepository $filesRepository;

    /**
     * init - initialize private variables
     *
     * @access public
     *
     * @param UserService    $userService
     * @param FileRepository $filesRepository
     *
     * @return void
     */
    public function init(
        UserService $userService,
        FileRepository $filesRepository
    ): void {
        $this->userService = $userService;
        $this->filesRepository = $filesRepository;
    }

    /**
     * get - handle get requests
     *
     * @access public
     *
     * @param array $params parameters or body of the request
     *
     * @return Response
     *
     * @throws BindingResolutionException
     */
    public function get(array $params): Response
    {
        if (isset($params['projectUsersAccess'])) {
            if ($params['projectUsersAccess'] == 'current') {
                $projectId = $_SESSION['currentProject'];
            } else {
                $projectId = $params['projectUsersAccess'];
            }

            $users = $this->userService->getUsersWithProjectAccess($_SESSION['userdata']['id'], $projectId);

            return $this->tpl->displayJson($users);
        }

        if (isset($params["profileImage"])) {
            $svg = $this->userService->getProfilePicture($params["profileImage"]);
            if (is_array($svg)) {
                $file = app()->make(FileuploadCore::class);
                return match ($svg['type']) {
                    'uploaded' => $file->displayImageFile($svg['filename']),
                    'generated' => $file->displayImageFile('avatar', $svg['filename']),
                };
            }

            $response = new Response($svg->toXMLString());
            $response->headers->set('Content-type', 'image/svg+xml');

            if (false === app()->make(Environment::class)->debug) {
                $response->headers->set("Pragma", 'public');
                $response->headers->set("Cache-Control", 'max-age=86400');
            }

            return $response;
        }

        return $this->tpl->displayJson(['status' => 'failure'], 500);
    }

    /**
     * post - handle post requests
     *
     * @access public
     *
     * @param array $params parameters or body of the request
     *
     * @return Response
     *
     * @throws BindingResolutionException
     */
    public function post(array $params): Response
    {
        if (! isset($_FILES['file'])) {
            return $this->tpl->displayJson(['error' => 'File not included'], 400);
        }

        // Updating User Image
        $_FILES['file']['name'] = "userPicture.png";

        $this->userService->setProfilePicture($_FILES, $_SESSION['userdata']['id']);

        $_SESSION['msg'] = "PICTURE_CHANGED";
        $_SESSION['msgT'] = "success";

        return $this->tpl->displayJson(['status' => 'ok']);
    }

    /**
     * put - Special handling for settings
     *
     * @param array $params parameters or body of the request
     *
     * @return Response
     */
    public function patch(array $params): Response
    {
        if (
            count(array_intersect(array_keys($params), ['patchModalSettings', 'patchViewSettings', 'patchMenuStateSettings'])) == 0
            || (!empty($params['patchModalSettings']) && empty($params['settings']))
            || (!empty($params['patchViewSettings']) && empty($params['value']))
            || (!empty($params['patchMenuStateSettings']) && empty($params['value']))
        ) {
            return $this->tpl->displayJson(['status' => 'failure', 'error' => 'Required params not included in request'], 400);
        }

        $success = false;
        foreach (
            [
                'patchModalSettings' => fn () => $this->userService->updateUserSettings("modals", $params['settings'], 1),
                'patchViewSettings' => fn () => $this->userService->updateUserSettings("views", $params['patchViewSettings'], $params['value']),
                'patchMenuStateSettings' => fn () => $this->userService->updateUserSettings("views", "menuState", $params['value']),
            ] as $param => $callback
        ) {
            if (! isset($params[$param])) {
                continue;
            }

            $success = $callback();
            break;
        }

        if ($success) {
            return $this->tpl->displayJson(['status' => 'ok']);
        }

        return $this->tpl->displayJson(['status' => 'failure'], 500);
    }

    /**
     * delete - handle delete requests
     *
     * @access public
     *
     * @param array $params parameters or body of the request
     *
     * @return Response
     */
    public function delete(array $params): Response
    {
        return $this->tpl->displayJson(['status' => 'Not implemented'], 501);
    }
}
