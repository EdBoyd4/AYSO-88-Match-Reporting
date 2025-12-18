function setSanctionedPartyDescriptionElements(checkedRadio2, partyDescriptionSection){
    let partyDescriptionLabel = partyDescriptionSection.getElementsByTagName('label')[0];
    let partyDescriptionP = partyDescriptionSection.getElementsByTagName('p')[0];
    if(checkedRadio2.value === 'player'){
        partyDescriptionLabel.textContent = 'Please Provide a Brief Identifying Description of the Player';
        partyDescriptionP.style.display ='flex';
        partyDescriptionP.textContent = '(Just Team and Player Jersey Number is OK):';
    }else{
        partyDescriptionLabel.textContent = 'Please Provide a Brief Identifying Description of the Coach';
        partyDescriptionP.style.display ='none';
    };
}   

function registerSanctionSectionVisibility(buttonsSanctionEntry){
    buttonsSanctionEntry.forEach((button, index) => {
        button.addEventListener('click', function () {
            const content = document.querySelector(`#section_sanction-info-${index + 1}`);
            const nextSection = document.querySelector(`#section_sanction-entry-${index + 2}`);
            const elementsInSection = content.querySelectorAll('input, textarea');
            
            if (button.textContent === 'I Had to Caution / Send-Off Someone' || button.textContent === 'I Had to Caution / Send-Off Someone Else') {
                // Toggle to clicked state
                button.textContent = 'Disregard This Sanction Report';
                content.style.display = 'flex';
                // disable the elements
                elementsInSection.forEach(element => {
                    element.disabled = false;
                    element.required = true;
                });
                if (nextSection) {
                    nextSection.style.display = 'flex';
                }
            } else {
                // Toggle to unclicked state
                if(button.id === 'button_sanction-entry-1'){
                    button.textContent = 'I Had to Caution / Send-Off Someone';
                }else{
                    button.textContent = 'I Had to Caution / Send-Off Someone Else';
                }                
                content.style.display = 'none';
                // disable the elements
                elementsInSection.forEach(element => {
                    element.disabled = true;
                    element.required = false;
                });
                hideFollowingSections(index + 2);  // Hide all subsequent sections
            }
        });
        
        const radios1 = document.querySelectorAll(`input[name="sanctionLevel${index + 1}"]`);
        const radios2 = document.querySelectorAll(`input[name="sanctionParty${index + 1}"]`);
        const partyDescriptionSection = document.querySelector(`#section_sanction-party-description-${index + 1}`);
        const eventDescriptionSection = document.querySelector(`#section_sanction-summary-${index + 1}`);

        radios1.forEach(radio => {
            radio.addEventListener('change', () => checkRadioGroups(radios1, radios2, partyDescriptionSection, eventDescriptionSection));
        });

        radios2.forEach(radio => {
            radio.addEventListener('change', () => checkRadioGroups(radios1, radios2, partyDescriptionSection, eventDescriptionSection));
        });
    });

}

function hideFollowingSections(startIndex) {
    let i = startIndex;
    let section = document.querySelector(`#section_sanction-entry-${i}`);
    while (section) {
        section.style.display = 'none';
        const content = document.querySelector(`#section_sanction-info-${i}`);
        content.style.display = 'none';
        const elementsInSection = content.querySelectorAll('input, textarea');
        // disable the elements
        elementsInSection.forEach(element => {
            element.disabled = true;
        });
        const button = document.querySelector(`#button_sanction-entry-${i}`);
        button.textContent = 'I Had to Caution / Send-Off Someone Else';
        i++;
        section = document.querySelector(`#section_sanction-entry-${i}`);
    }
}

function checkRadioGroups(radios1, radios2, partyDescriptionSection, eventDescriptionSection) {
    const group1Checked = Array.from(radios1).some(radio => radio.checked);
    const group2Checked = Array.from(radios2).some(radio => radio.checked);
    const checkedRadio2 = Array.from(radios2).find(radio => radio.checked);
    
    if (group1Checked && group2Checked) {
        setSanctionedPartyDescriptionElements(checkedRadio2, partyDescriptionSection);
        partyDescriptionSection.style.display = 'flex';
        eventDescriptionSection.style.display = 'flex';
    } else {
        partyDescriptionSection.style.display = 'none';
        eventDescriptionSection.style.display = 'none';
    }
}

function registerFormSanctionElements(form) {
    const buttonsSanctionEntry = form.querySelectorAll('.button_sanction-entry');

    registerSanctionSectionVisibility(buttonsSanctionEntry);
}