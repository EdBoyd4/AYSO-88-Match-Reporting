<?php

class GameCardsPhotoEntrySegment {
    private int $pairNumber;
    private ?int $matchNumber;

    public function __construct(int $pairNumber, ?int $matchNumber = null) {
        $this->pairNumber = $pairNumber;
        $this->matchNumber = $matchNumber;
    }

    private function getPairNumberText(): string {
        switch ($this->pairNumber) {
            case 1:
                return 'Photo 1';
            case 2:
                return 'Photo 2';
            default:
                return 'ERROR';
        }
    }

    // What used to be the full legend text, before it was shortened to just
    // "Photo 1"/"Photo 2" -- still real instructional content (which side of
    // which card to photograph), now shown in the "See Example" modal
    // instead, alongside the example image it's describing.
    private function getPairInstructionText(): string {
        switch ($this->pairNumber) {
            case 1:
                return 'Photo of Front of Card 1, and Back of Card 2';
            case 2:
                return 'Photo of Front of Card 2, and Back of Card 1';
            default:
                return 'ERROR';
        }
    }

    public function render(): void {
        $pairNumberText = $this->getPairNumberText();
        $pairInstructionText = $this->getPairInstructionText();
        $matchPrefix = ($this->matchNumber === null ? '' : 'match-' . $this->matchNumber . '-');
        $matchNamePrefix = ($this->matchNumber === null ? '' : $this->matchNumber);
        
        $legendId = 'legend_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber;
        // Fieldset (not a <section>, now that it has a <legend>) so this
        // picks up the same grey border/bold legend every other fieldset on
        // the form has. The former <label> is now that legend -- the file
        // input keeps its accessible name via aria-labelledby since a
        // <legend> isn't itself a label for the control inside it.
        echo '<fieldset id="section_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber . '-photo-entry" class="section_photo-gamecards">
            <legend id="' . $legendId . '" class="label_gamecards_photo">' . $pairNumberText . '</legend>
            <input type="file" accept="image/*"
                name="game' . $matchNamePrefix . 'CardsPhoto' . $this->pairNumber . '"
                id="photo_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber . '"
                class="photo_gamecard-pair"
                aria-labelledby="' . $legendId . '" required>
                <button
                type="button" 
                id="button_gamecard-photo-example-' . $this->pairNumber . '" 
                class="button-userhelper-example"
                >
                See Example
                </button>
                <!-- Modal -->
                <div 
                id="imageAlert' . $this->pairNumber . '" 
                class="modal"
                >
                    <div 
                    class="modal-content"
                    >
                        <span id="closeExample' . $this->pairNumber . '" class="close">&times;</span>
                        <p class="modal-instruction">' . $pairInstructionText . '</p>
                        <img src="/image_example-' . $this->pairNumber . '.jpg" alt="' . $pairInstructionText . '" />
                    </div>
                </div>
        </fieldset>
        <section id="photo_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber . '-preview" class="section_photo-gamecards-preview"></section>';
    }
}
