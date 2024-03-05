<?php

namespace Leantime\Domain\Cron\Services {

    use Leantime\Core\Environment;
    use Leantime\Core\Eventhelpers;
    use Leantime\Domain\Audit\Repositories\Audit;
    use Leantime\Domain\Queue\Services\Queue;
    use Leantime\Domain\Reports\Services\Reports;
    use PHPMailer\PHPMailer\Exception;

    /**
     *
     */
    class Cron
    {
        use Eventhelpers;

        private Audit $auditRepo;
        private Queue $queueSvc;
        private Environment $Environment;
        private Environment $environment;
        private Reports $reportService;

        private int $cronExecTimer = 60;

        /**
         * @param Audit       $auditRepo
         * @param Queue       $queueSvc
         * @param Environment $environment
         */
        public function __construct(Audit $auditRepo, Queue $queueSvc, Environment $environment, Reports $reportService)
        {
            $this->auditRepo =  $auditRepo;
            $this->queueSvc = $queueSvc;
            $this->environment = $environment;
            $this->reportService = $reportService;
        }

        /**
         * @return bool
         * @throws Exception
         */
        public function runCron(): bool
        {

            $lastEvent = $this->auditRepo->getLastEvent('cron');

            if (isset($lastEvent['date'])) {
                $lastCronEvent = strtotime($lastEvent['date']);
            } else {
                $lastCronEvent = 0;
            }

            // Using audit system to prevent too frequent executions
            $nowDate = time();
            $timeSince = abs($nowDate - $lastCronEvent);

            if ($timeSince < $this->cronExecTimer) {
                if ($this->environment->debug) {
                    error_log("Last cron execution was on " . $lastEvent['date'] . " plz come back later");
                }

                return false;
            }

            //Process other events
            self::dispatch_event("addJobToBeginning", $lastEvent);

            //Process Telemetry Start
            $telemetryResponse = $this->reportService->sendAnonymousTelemetry();

            //Daily Ingestion
            $this->reportService->dailyIngestion();

            //Process Queue
            $this->queueSvc->processQueue();

            if ($telemetryResponse != null) {
                try {
                    $telemetryResponse->wait();
                } catch (Exception $e) {
                    error_log($e);
                }
            }

            //Clean Audit Table
            $this->auditRepo->pruneEvents();

            //Process other events
            self::dispatch_event("addJobToEnd", $lastEvent);

            return true;
        }
    }
}
