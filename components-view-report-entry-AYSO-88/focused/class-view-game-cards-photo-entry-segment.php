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
                return 'Photo of Front of Card 1, and Back of Card 2';
            case 2:
                return 'Photo of Front of Card 2, and Back of Card 1';
            default:
                return 'ERROR';
        }
    }

    public function render(): void {
        $pairNumberText = $this->getPairNumberText();
        $matchPrefix = ($this->matchNumber === null ? '' : 'match-' . $this->matchNumber . '-');
        $matchNamePrefix = ($this->matchNumber === null ? '' : $this->matchNumber);
        
        echo '<section id="section_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber . '-photo-entry" class="section_photo-gamecards">
            <label for="photo_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber . '" class="label_gamecards_photo">' . $pairNumberText . ':
            <input type="file" accept="image/*"
                name="game' . $matchNamePrefix . 'CardsPhoto' . $this->pairNumber . '" 
                id="photo_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber . '" 
                class="photo_gamecard-pair" required></label>
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
                        <img src="/image_example-' . $this->pairNumber . '.jpg" alt="Alert Image" />
                    </div>
                </div>
        </section>
        <section id="photo_' . $matchPrefix . 'gamecard-pair-' . $this->pairNumber . '-preview"></section>';
    }
}
