<?php declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\BackgroundProcess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    const LOG_PATH = 'var/log/out.log';
    const ERROR_PATH = 'var/log/error.log';
    const PID_FILE_PATH = 'var/tmp/app:check-currency.pid';

    private string $rootPath;

    public function __construct(
        private ParameterBagInterface $params,
        private Filesystem $filesystem,
    )
    {
        $this->rootPath = $this->params->get('kernel.project_dir');
    }

    /**
     * @Route(path="/", methods={"GET"})
     */
    public function main(Request $request): Response
    {
        return $this->render('form.html.twig', $this->getParamsForTemplate());
    }

    /**
     * @Route(path="/start", methods={"GET"})
     */
    public function startBot(Request $request): RedirectResponse
    {
        $command = sprintf("php %s/bin/console app:check-currency", $this->rootPath);
        $outLog = sprintf("%s/%s", $this->rootPath, self::LOG_PATH);
        $errorLog = sprintf("%s/%s", $this->rootPath, self::ERROR_PATH);

        $process = new BackgroundProcess($command);
        $process->run($outLog, false, $errorLog);

        return $this->redirect('/');
    }

    /**
     * @Route(path="/stop", methods={"GET"})
     */
    public function stopBot(Request $request): Response
    {
        $pidFilePath = sprintf('%s/var/tmp/%s.pid', $this->rootPath, "app:check-currency");

        if ($this->filesystem->exists($pidFilePath)) {
            $pidCommand = (int)file_get_contents($pidFilePath);
            BackgroundProcess::stopByPid($pidCommand);
        }

        return $this->redirect('/');
    }

    private function getParamsForTemplate(): array
    {
        $rootPath = $this->params->get('kernel.project_dir');
        $logPath = sprintf("%s/%s", $rootPath, self::LOG_PATH);
        $errorPath = sprintf("%s/%s", $rootPath, self::ERROR_PATH);
        $pidFilePath = sprintf("%s/%s", $rootPath, self::PID_FILE_PATH);

        if ($this->filesystem->exists($logPath)) {
            $logs = file_get_contents($logPath);
        }

        if ($this->filesystem->exists($errorPath)) {
            $error = file_get_contents($errorPath);
        }

        if ($this->filesystem->exists($pidFilePath)) {
            $pid = file_get_contents($pidFilePath);
        }

        return [
            'log' => $logs ?? '',
            'error' => $error ?? '',
            'pid' => $pid ?? false,
        ];
    }
}
