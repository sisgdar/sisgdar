<?php

namespace Leantime\Domain\Wiki\Controllers {

    use Leantime\Core\Controller;
    use Leantime\Core\Frontcontroller;
    use Leantime\Domain\Auth\Models\Roles;
    use Leantime\Domain\Auth\Services\Auth;
    use Leantime\Domain\Wiki\Repositories\Wiki as WikiRepository;

    /**
     *
     */
    class DelWiki extends Controller
    {
        private WikiRepository $wikiRepo;

        /**
         * init - init
         *
         * @access public
         */
        public function init(WikiRepository $wikiRepo)
        {
            $this->wikiRepo = $wikiRepo;
        }

        /**
         * run - display template and edit data
         *
         * @access public
         */
        public function run()
        {

            Auth::authOrRedirect([Roles::$owner, Roles::$admin, Roles::$manager, Roles::$editor]);

            if (isset($_GET['id'])) {
                $id = (int)($_GET['id']);
            }

            if (isset($_POST['del']) && isset($id)) {
                $this->wikiRepo->delWiki($id);

                unset($_SESSION['currentIdeaCanvas']);
                $this->tpl->setNotification($this->language->__("notification.wiki_deleted"), "success", "wiki_deleted");
                return Frontcontroller::redirect(BASE_URL . "/wiki/show");
            }

            return $this->tpl->displayPartial('wiki.delWiki');
        }
    }
}
