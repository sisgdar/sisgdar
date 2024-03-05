<?php

namespace Leantime\Domain\Goalcanvas\Services {

    use Leantime\Domain\Goalcanvas\Repositories\Goalcanvas as GoalcanvaRepository;

    /**
     *
     */
    class Goalcanvas
    {
        private GoalcanvaRepository $goalRepository;
        public array $reportingSettings = [
            "linkonly",
            "linkAndReport",
            "nolink",
        ];

        /**
         * @param GoalcanvaRepository $goalRepository
         */
        public function __construct(GoalcanvaRepository $goalRepository)
        {
            $this->goalRepository = $goalRepository;
        }

        /**
         * @param int $id
         * @return array
         */
        public function getCanvasItemsById(int $id): array
        {

            $goals = $this->goalRepository->getCanvasItemsById($id);

            if ($goals) {
                foreach ($goals as &$goal) {
                    $progressValue = 0;
                    $goal['goalProgress'] = 0;
                    $total = $goal['endValue'] - $goal['startValue'];
                    //Skip if total value is 0.
                    if ($total <= 0) {
                        continue;
                    }

                    if ($goal['setting'] == "linkAndReport") {
                        //GetAll Child elements
                        $currentValueSum = $this->getChildGoalsForReporting($goal['id']);

                        $goal['currentValue'] = $currentValueSum;
                        $progressValue = $currentValueSum - $goal['startValue'];
                    } else {
                        $progressValue = $goal['currentValue'] - $goal['startValue'];
                    }

                    $goal['goalProgress'] = round($progressValue / $total, 2) * 100;
                }
            }

            return $goals;
        }

        /**
         * @param $parentId
         * @return int|mixed
         */
        /**
         * @param $parentId
         * @return int|mixed
         */
        public function getChildGoalsForReporting($parentId): mixed
        {

            //Goals come back as rows for levl1 and lvl2 being columns, so
            //goal A | goalChildA
            //goal A | goalChildB
            //goal B
            //Checks if first level is also link+report or just link
            $goals = $this->goalRepository->getCanvasItemsByKPI($parentId);
            $currentValueSum = 0;
            foreach ($goals as $child) {
                if ($child['setting'] == "linkAndReport") {
                    $currentValueSum = $currentValueSum + $child['childCurrentValue'];
                } else {
                    $currentValueSum = $currentValueSum + $child["currentValue"];
                }
            }

            return $currentValueSum;
        }

        /**
         * @param $parentId
         * @return array
         */
        public function getChildrenbyKPI($parentId): array
        {

            $goals = array();
            //Goals come back as rows for levl1 and lvl2 being columns, so
            //goal A | goalChildA
            //goal A | goalChildB
            //goal B
            //Checks if first level is also link+report or just link
            $children = $this->goalRepository->getCanvasItemsByKPI($parentId);

            foreach ($children as $child) {
                //Added Child already? Look for child of child
                if (!isset($goals[$child['id']])) {
                    $goals[$child['id']] = array(
                        "id" => $child['id'],
                        "title" => $child['title'],
                        "startValue" => $child['startValue'],
                        "endValue" => $child['endValue'],
                        "currentValue" => $child['currentValue'],
                        "metricType" => $child['metricType'],
                        "boardTitle" => $child['boardTitle'],
                        "canvasId" => $child['canvasId'],
                        "projectName" => $child['projectName'],
                    );
                }

                if ($child['childId'] != '') {
                    if (isset($goals[$child['childId']]) === false) {
                        $goals[$child['childId']] = array(
                            "id" => $child['childId'],
                            "title" => $child['childTitle'],
                            "startValue" => $child['childStartValue'],
                            "endValue" => $child['childEndValue'],
                            "currentValue" => $child['childCurrentValue'],
                            "metricType" => $child['childMetricType'],
                            "boardTitle" => $child['childBoardTitle'],
                            "canvasId" => $child['childCanvasId'],
                            "projectName" => $child['childProjectName'],
                        );
                    }
                }
            }

            return $goals;
        }

        /**
         * @param $projectId
         * @return array
         */
        public function getParentKPIs($projectId): array
        {

            $kpis = $this->goalRepository->getAllAvailableKPIs($projectId);

            $goals = array();

            //Checks if first level is also link+report or just link
            foreach ($kpis as $kpi) {
                $goals[$kpi['id']] = array(
                    "id" => $kpi['id'],
                    "description" => $kpi['description'],
                    "project" => $kpi['projectName'],
                    "board" => $kpi['boardTitle'],
                );
            }

            return $goals;
        }

        public function getGoalsByMilestone($milestoneId): array
        {

            $goals = $this->goalRepository->getGoalsByMilestone($milestoneId);

            return $goals;
        }

        public function updateGoalboard($values) {
            return $this->goalRepository->updateCanvas($values);
        }

        public function createGoalboard($values) {
            return $this->goalRepository->addCanvas($values);
        }

        public function getSingleCanvas($id) {
            return $this->goalRepository->getSingleCanvas($id);
        }


    }

}
