<?php

namespace Concrete\Package\LassoCrm\Lasso;

class QuestionAnswerParser
{
    /**
     * @return array<string, string>
     */
    public function parse(?string $answers): array
    {
        $options = [];

        if (empty($answers)) {
            return $options;
        }

        foreach (preg_split('/\r\n|\r|\n/', $answers) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[([^\]]+)\]\s*(.*)$/', $line, $matches)) {
                $options[$matches[1]] = trim($matches[2]);
            }
        }

        return $options;
    }
}
