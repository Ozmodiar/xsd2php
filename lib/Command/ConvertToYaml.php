<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Goetas\Xsd\XsdToPhp\AbstractConverter;
use Goetas\Xsd\XsdToPhp\Naming\NamingStrategy;

class ConvertToYaml extends AbstractConvert
{

    /**
     *
     * @see Console\Command\Command
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('convert:jms-yaml');
        $this->setDescription('Convert XSD definitions into YAML metadata for JMS Serializer');
        $this->addOption('ns-xml', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '');
    }

    protected function getConverterter(NamingStrategy $naming)
    {
        return new YamlConverter($naming);
    }

    protected function convert(AbstractConverter $converter, array $schemas, array $targets, InputInterface $input, OutputInterface $output)
    {
        $nsXml = $input->getOption('ns-xml');
        $nsXmls = array();
        foreach ($nsXml as $ns) {
            list ($xmlNs, $prefix) = explode(";", $ns);
            $nsXmls[$xmlNs] = $prefix;
        }
        $converter->setXmlNamespaces($nsXmls);

        $items = $converter->convert($schemas);

        $dumper = new Dumper();

        $pathGenerator = new Psr4PathGenerator($targets);
        $progress = $this->getHelperSet()->get('progress');

        $progress->start($output, count($items));

        foreach ($items as $item) {
            $progress->advance(1, true);
            $output->write(" Item <info>" . key($item) . "</info>... ");

            $source = $dumper->dump($item, 10000);
            $output->write("created source... ");

            $path = $pathGenerator->getPath($item);
            $bytes = file_put_contents($path, $source);
            $output->writeln("saved source <comment>$bytes bytes</comment>.");
        }
        $progress->finish();
    }
}
