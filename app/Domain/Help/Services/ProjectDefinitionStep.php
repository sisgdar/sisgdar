<?php

namespace Leantime\Domain\Help\Services;

use Leantime\Core\Eventhelpers;
use Leantime\Domain\Help\Contracts\OnboardingSteps;
use Leantime\Domain\Projects\Services\Projects;
use Leantime\Domain\Setting\Repositories\Setting;

/**
 *
 */
class ProjectDefinitionStep implements OnboardingSteps
{
    use Eventhelpers;

    public function __construct(
        private Setting $settingsRepo,
        private Projects $projectService
    ) {}

    public function getTitle(): string
    {
       return "Describe your project";
    }

    public function getAction() : string{
        // TODO: Implement getAction() method.
    }

    public function getTemplate() : string{
        return "help.projectDefinitionStep";
    }


    public function handle($params): bool
    {

        $description = "";

        if (isset($params['accomplish'])) {
            $description .= "<h3>" . __('label.what_are_you_trying_to_accomplish') . "</h3>";
            $description .= "" . $params['accomplish'];
        }

        if (isset($params['worldview'])) {
            $description .= "<br /><h3>" . __('label.how_does_the_world_look_like') . "</h3>";
            $description .= "" . $params['worldview'];
        }

        if (isset($params['whyImportant'])) {
            $description .= "<br /><h3>" . __('label.why_is_this_important') . "</h3>";
            $description .= "" . $params['whyImportant'];
        }

        $this->projectService->patch($_SESSION['currentProject'], array("details" => $description));
        $this->projectService->changeCurrentSessionProject($_SESSION['currentProject']);

        $this->settingsRepo->saveSetting("companysettings.completedOnboarding", true);

        return true;

    }

}
