<?php

class IdeHelperCommand extends CConsoleCommand {

    private $comment = '';

    public function actionIndex() {
        $configFiles = glob($this->configPath . '/*.php');
        $configFile = $this->choseConfigFile($configFiles);
        $config = require_once $configFile;

        if (!isset($config['components'])) {
            $this->println('No components found in config file.');
            Yii::app()->end();
        }

        foreach ($config['components'] as $alias => $conf) {
            if (isset($conf['class'])) {
                $this->addComment('property', $conf['class'], $alias);
            }
        }

        $this->generateHelper();

    }

    public function getConfigPath() {
        $defaultConfigPath = __DIR__ . '/../config';
        if (!file_exists($defaultConfigPath)) {
            $configPath = $this->prompt('The config path dose not set default, please special the config path:');
            if (!file_exists($configPath)) {
                $this->getConfigPath();
            }
        } else {
            $configPath = $defaultConfigPath;
        }
        return $configPath;
    }

    public function generateHelper() {
        $text = <<<DOC
<?php

/**
 * Class Application
 *
$this->comment */

class Application extends CApplication {
    public function processRequest() {}
}

class Yii extends YiiBase {
    private static \$_app;

    /**
     * @return Application
     */
    public static function app()
    {
        return self::\$_app;
    }
}
DOC;

        $filePath = Yii::app()->basePath . '/_ide_helper.php';
        file_put_contents($filePath, $text);
        $this->println("IDE Helper File: $filePath generate success.");
    }

    private function choseConfigFile($configFiles) {

        $this->println('Chose a config file:');

        foreach ($configFiles as $i => $configFile) {
            printf("%d. %s\n", $i + 1, basename($configFile));
        }

        $chosen = $this->prompt(sprintf("\nChose [1-%d]:", count($configFiles)));
        $index = intval($chosen) - 1;

        if (!isset($configFiles[$index])) {
            $this->println('Bad Input, please try again.');
            $this->choseConfigFile($configFiles);
        }

        return $configFiles[$index];
    }

    private function addComment($type, $dataType, $property) {
        if (strpos($dataType, '.')) {
            $parts = explode('.', $dataType);
            $dataType = end($parts);
        }
        $this->comment .= " * @$type $dataType \$$property\n";
    }

    private function println($msg, $break = true) {
        $break && fwrite(STDOUT, PHP_EOL);
        fwrite(STDOUT, $msg . PHP_EOL);
        $break && fwrite(STDOUT, PHP_EOL);
    }
} 