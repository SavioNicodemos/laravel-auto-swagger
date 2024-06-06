<?php

namespace AutoSwagger\Docs\Formatters;

use AutoSwagger\Docs\Exceptions\ExtensionNotLoaded;

/**
 * Class YamlFormatter
 * @package AutoSwagger\Docs\Formatters
 */
class YamlFormatter extends AbstractFormatter
{

    /**
     * @inheritDoc
     * @return string
     * @throws ExtensionNotLoaded
     */
    public function format(): string
    {
        if (!extension_loaded('yaml')) {
            throw new ExtensionNotLoaded('YAML extends must be loaded to use the `yaml` output format');
        }
        return yaml_emit($this->documentation);
    }
}
