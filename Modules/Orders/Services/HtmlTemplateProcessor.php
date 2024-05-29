<?php

namespace Modules\Orders\Services;

use DOMDocument;
use DOMNode;
use DOMText;
use DOMElement;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class HtmlTemplateProcessor
{

    private DOMDocument $document;

    private array $valuesToReplace = [];

    public function __construct(?string $htmlData)
    {
        if(!$htmlData) {
            throw ValidationException::withMessages([
                'errors' => 'Отсутсвует шаблон HTML'
            ]);
        }
        $this->document = new DOMDocument();
        $this->document->loadHTML(mb_convert_encoding($htmlData, 'HTML-ENTITIES', 'UTF-8'));
    }

    public function setValue(string $search, ?string $replace): static
    {
        $this->valuesToReplace[$search] = $replace ?? '';

        return $this;
    }

    public function cloneRowAndSetValues(string $search, array $values): void
    {
        $elements = $this->document->getElementsByTagName('tr');
        $findAny = false;
        /** @var DOMElement $tr */
        foreach ($elements as $tr) {

            if ($this->nodeContainValue($tr, '${' . $search . '}')) {
                $findAny = true;
                $pureClone = $tr;
                $oldTr = $tr->cloneNode(true);
                foreach ($values as $key => $valuesData) {
                    if($key !== 0) {
                        $pureClone = $oldTr->cloneNode(true);
                    }
                    foreach ($valuesData as $search => $replace) {
                        if ($node = $this->nodeContainValue($pureClone, '${' . $search . '}')) {
                            $node->nodeValue = Str::replace(
                                '${' . $search . '}',
                                $replace,
                                $node->nodeValue
                            );
                            continue;
                        }
                        foreach ($pureClone->childNodes as $childNode) {
                            if($childNode instanceof DOMText) {
                                $childNode = $childNode->parentNode;
                            }
                            if ($node = $this->nodeContainValue($childNode, '${' . $search . '}')) {
                                $node->nodeValue = Str::replace(
                                    '${' . $search . '}',
                                    $replace,
                                    $node->nodeValue
                                );
                            }
                        }
                    }
                    if($key !== 0) {
                        $tr->append($pureClone);
                    }
                }
            }
        }
        throw_if(!$findAny, new UnprocessableEntityHttpException());
    }

    private function nodeContainValue(DOMElement $node, string $search): ?DOMElement
    {
        if (trim($node->nodeValue) === $search) {
            return $node;
        }
        foreach ($node->childNodes as $childNode) {
            if (trim($childNode->nodeValue) === $search) {
                return $childNode;
            }
        }

        return null;
    }

    public function getResult(): string
    {
        $data = $this->document->saveHTML($this->document->documentElement);

        foreach ($this->valuesToReplace as $search => $replace) {
            $data = Str::replace(
                '${' . $search . '}',
                $replace,
                $data
            );
        }

        return $data;
    }

    public function saveAs(string $path)
    {

    }


}