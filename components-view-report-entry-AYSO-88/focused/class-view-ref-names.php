<?php

class RefNames {
    private int $refNumber;
    private ?string $refName;

    public function __construct(int $refNumber, ?string $refName = null) {
        $this->refNumber = $refNumber;
        $this->refName = $refName;
    }

    public function render(): void {
        $refRole = '';
        $refRoleText = '';
        $entryRequired = '';

        switch ($this->refNumber) {
            case 0:
                $refRole = 'center';
                $refRoleText = 'Your';
                $entryRequired = ' required';
                break;

            case 1:
                $refRole = 'ar1';
                $refRoleText = 'AR 1\'s';
                break;

            case 2:
                $refRole = 'ar2';
                $refRoleText = 'AR 2\'s';
                break;

            default:
                // for modification - requires includeOnce : environmentConfig.php with : define('IS_DEV', true); // or false on the A2 server

                /* if (IS_DEV) {
                    throw new InvalidArgumentException("Invalid referee number: " . $this->refNumber);
                } else {
                    error_log("Invalid referee number in refNames(): " . $this->refNumber);
                    return;
                } */
                return;
        }

        echo '<section id="section-' . $refRole . '-ref-entry"
                     class="match-report-descriptor__ref match-report-descriptor__ref--' . $refRole . '">

                <label for="text-ref-' . $refRole . '"
                       id="label-text-ref-' . $refRole . '">
                       ' . $refRoleText . ' Name:
                </label>

                <input type="text"
                        name="' . $refRole . '"
                        id="text-ref-' . $refRole . '"
                        class="form-control"
                        ' . ($this->refName === null ? '' : 'value="' . $this->refName . '"') . $entryRequired . '>
            </section>';
    }
}
