<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:check-currency',
    description: '',
    hidden: false
)]
class CheckCurrencyDiffCommand extends Command
{
    CONST URL_CURRENCY = 'https://www.ecb.europa.eu/stats/policy_and_exchange_rates/euro_reference_exchange_rates/html/usd.xml';
    CONST TIME_INTERVAL = 10 * 60;

    private string $pidFilePath;
    private int $pid;
    private bool $shouldStop = false;
    private ?int $saveTime = null;

    private OutputInterface $output;

    public function __construct(
        private Filesystem $filesystem,
        private ParameterBagInterface $params
    )
    {
        parent::__construct();

        $rootPath = $this->params->get('kernel.project_dir');
        $this->pidFilePath = sprintf('%s/var/tmp/%s.pid', $rootPath, $this->getName());
        $this->pid = getmypid();

        if ($this->filesystem->exists($this->pidFilePath)) {
            $this->stopCommand();
            return;
        }

        $this->filesystem->appendToFile($this->pidFilePath, $this->pid);
        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        if ($this->shouldStop) {
            $this->writeError("The bot is already running");
            return Command::FAILURE;
        }

        while (true) {
            if ($this->shouldStop) break;
            if ($this->saveTime && time() - $this->saveTime < self::TIME_INTERVAL) continue;

            $this->saveTime = time();

            try {
                $this->writeOut($this->parsingCurrency());
            } catch (\Throwable $e) {
                $this->writeError($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    //TODO Take out this logic
    private function parsingCurrency(): string
    {
        $xmlContent = file_get_contents(self::URL_CURRENCY);

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = FALSE;

        $doc->loadXML($xmlContent);
        $listNodes = $doc->getElementsByTagName('Obs');

        $value = $listNodes->item($listNodes->length-1)->attributes->getNamedItem('OBS_VALUE')->textContent;

        return sprintf('Last EUR/USD = %s', $value);
    }

    private function writeOut(string $message): void
    {
        $messageOut = sprintf('%s [date_machine_readable] - %s - %s',
            (new \DateTimeImmutable())->format('G:i:s'),
            $this->pid,
            $message
        );

        $this->output->writeln($messageOut);
    }

    private function writeError(string $message): void
    {
        $messageOut = sprintf("[date_machine_readable] - %s - %s\n",
            $this->pid,
            $message,
        );
        fwrite(STDERR, $messageOut);
    }

    private function stopCommand()
    {
        $this->shouldStop = true;
    }

    public function __destruct()
    {
        $this->writeOut('Bot stop');

        if (!$this->saveTime) return;

        $this->filesystem->remove($this->pidFilePath);
    }
}
