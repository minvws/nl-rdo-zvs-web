*** Settings ***
Documentation       Suite for testing petitions (zaken) functionalities in Zaakvolgsysteem.

Resource            ../resources/Setup.resource
Library             Collections
Library             DateTime
Library             DependencyLibrary
Library             String
Library             OperatingSystem

Suite Setup         Setup
Test Teardown       Take Screenshot    fullPage=True


*** Variables ***
${RESULT_FORMAT}    %d/%m/%Y


*** Test Cases ***
Nieuwe Zaak Voor Team A Aanmaken
    [Documentation]    Nieuwe zaak voor Team A aanmaken
    [Tags]    tc_reg_zaken_01
    Go To    /team-a/petitions
    Click    id=petitions-create-woo_verzoek
    Get Element By Role    heading    name=Nieuwe zaak
    Type Text    id=name    Zaak voor Team A
    Type Text    id=description    Dit is een testzaak voor Team A
    ${button}    Get Element By Role    button    name=Aanmaken
    Click    ${button}
    Check For Notification    Opgeslagen

Zaak Van Team A Bewerken Door Indiener Gemachtigde En Behandelaar Toe Te Voegen
    [Documentation]    Zaak van Team A bewerken door Indiener, Gemachtigde en Behandelaar toe te voegen
    [Tags]    tc_reg_zaken_02
    Depends On Test    Nieuwe Zaak Voor Team A Aanmaken
    Go To    /team-a/petitions
    ${row}    Get Element By Role    row    name=Zaak voor Team A
    Click    ${row}

    Get Title    contains    Zaakoverzicht van

    Get Element Count    div#assign-user-block div.petition-property__content >> text=-    equals    1
    Get Element Count    div#contact-block div.petition-property__content >> text=-    equals    1

    ${button}    Get Element By Role    link    name=Behandelaar Bewerken
    Click    ${button}
    Select Options By    select#user-id    index    1
    ${save}    Get Element By Role    button    name=Wijs toe
    Click    ${save}
    Sleep    1s

    Get Title    contains    Zaakoverzicht van

    ${button}    Get Element By Role    link    name=Contacten Bewerken
    Click    ${button}
    Get Title    contains    Contacten koppelen

    ${rows}    Get Elements    h2 >> text=Contacten koppelen >> .. >> table.contacts-table tbody tr
    Click    ${rows}[0] >> text=Indiener
    Check For Notification    Opgeslagen
    Take Screenshot    fullPage=True
    Click    ${rows}[1] >> text=Gemachtigde
    Check For Notification    Opgeslagen
    ${back}    Get Element By Role    link    name=Terug naar Zaakdetails
    Click    ${back}

    Get Title    contains    Zaakoverzicht van

    Get Element Count    div#assign-user-block div.petition-property__content >> text=-    equals    0
    Get Element Count    div#contact-block div.petition-property__content >> text=-    equals    0

    Take Screenshot    fullPage=True

Nieuwe Zaak Voor Team B Aanmaken
    [Documentation]    Nieuwe zaak voor Team B aanmaken
    [Tags]    tc_reg_zaken_03
    Go To    /team-b/petitions
    Click    id=petitions-create-woo_verzoek
    Get Element By Role    heading    name=Nieuwe zaak
    Type Text    id=name    Zaak voor Team B
    Type Text    id=description    Dit is een testzaak voor Team B
    ${button}    Get Element By Role    button    name=Aanmaken
    Click    ${button}
    Check For Notification    Opgeslagen

Zaak Van Team B Bewerken Door Beleidsdirecties En Eigenschappen Toe Te Voegen Via De Sidebar
    [Documentation]    Zaak van Team B bewerken door Beleidsdirecties en Eigenschappen toe te voegen via de sidebar
    [Tags]    tc_reg_zaken_04
    Depends On Test    Nieuwe Zaak Voor Team B Aanmaken
    Go To    /team-b/petitions
    ${row}    Get Element By Role    row    name=Zaak voor Team B
    Click    ${row}

    Click    h2 >> text=Beleidsdirecties >> .. >> a.petition-property__edit
    ${options}    Create List
    ...    BPZ
    ...    CBG
    ...    CIBG
    ...    CIZ
    ...    DJ
    ...    Z
    ...    ZIN
    FOR    ${option}    IN    @{options}
        ${checkbox}    Get Element By Role    checkbox    name=${option}    exact=True
        Check Checkbox    ${checkbox}
    END

    ${save}    Get Element By Role    button    name=Opslaan
    Click    ${save}

    Get Text    div.timeline-item__note >> text=BPZ, CBG, CIBG, CIZ, DJ, Z, ZIN

# todo: check duur 42 dagen?

Nieuwe Zaak Voor Team C Aanmaken Met Ontvangst Bezwaar Binnen De Bezwaarperiode
    [Documentation]    Nieuwe zaak voor Team C aanmaken met ontvangst bezwaar binnen de Bezwaarperiode
    [Tags]    tc_reg_zaken_05
    Go To    /team-c/petitions
    Click    id=petitions-create-bezwaar
    Select Options By    select#petition-category-id    index    1
    ${two_weeks_ago}    Get Current Date    increment=-2w    result_format=${RESULT_FORMAT}
    Type Text    id=date_appealed_decision    ${two_weeks_ago}
    ${button}    Get Element By Role    button    name=Aanmaken
    Click    ${button}
    Check For Notification    Opgeslagen

    Take Screenshot    fullPage=True
    ${table}    Get Element    h2 >> text=Termijnen >> .. >> table
    ${objection_period_start_date}    Extract And Convert Table Date    ${table}    "Startdatum"    1
    ${diff}    Subtract Date From Date
    ...    ${objection_period_start_date}
    ...    ${two_weeks_ago}
    ...    verbose
    ...    date1_format=${RESULT_FORMAT}
    ...    date2_format=${RESULT_FORMAT}
    Should Be Equal    ${diff}    1 day

    ${objection_period_end_date}    Extract And Convert Table Date    ${table}    "Einddatum"    1

    ${standard_legal_name_start_date}    Extract And Convert Table Date    ${table}    "Startdatum"    2
    ${diff}    Subtract Date From Date
    ...    ${standard_legal_name_start_date}
    ...    ${objection_period_end_date}
    ...    verbose
    ...    date1_format=${RESULT_FORMAT}
    ...    date2_format=${RESULT_FORMAT}
    Should Be Equal    ${diff}    1 day

# todo: check duur 42 dagen?

Nieuwe Zaak Voor Team C Aanmaken Met Ontvangst Bezwaar Buiten De Bezwaarperiode
    [Documentation]    Nieuwe zaak voor Team C aanmaken met ontvangst bezwaar buiten de Bezwaarperiode
    [Tags]    tc_reg_zaken_06
    Go To    /team-c/petitions
    Click    id=petitions-create-bezwaar
    Select Options By    select#petition-category-id    index    1
    ${four_months_ago}    Get Current Date    increment=-4M    result_format=${RESULT_FORMAT}
    Type Text    id=date_appealed_decision    ${four_months_ago}
    ${button}    Get Element By Role    button    name=Aanmaken
    Click    ${button}
    Check For Notification    Opgeslagen

    ${table}    Get Element    h2 >> text=Termijnen >> .. >> table
    ${objection_period_start_date}    Extract And Convert Table Date    ${table}    "Startdatum"    1
    ${diff}    Subtract Date From Date
    ...    ${objection_period_start_date}
    ...    ${four_months_ago}
    ...    verbose
    ...    date1_format=${RESULT_FORMAT}
    ...    date2_format=${RESULT_FORMAT}
    Should Be Equal    ${diff}    1 day

    ${objection_period_end_date}    Extract And Convert Table Date    ${table}    "Einddatum"    1
    ${standard_legal_name_start_date}    Extract And Convert Table Date    ${table}    "Startdatum"    2
    ${diff}    Subtract Date From Date
    ...    ${standard_legal_name_start_date}
    ...    ${objection_period_end_date}
    ...    verbose
    ...    date1_format=${RESULT_FORMAT}
    ...    date2_format=${RESULT_FORMAT}
    Should Be Equal    ${diff}    1 day

Zaak Van Team C Bewerken Door Uitkomst En Kosten Toe Te Voegen Via De Sidebar
    [Documentation]    Zaak van Team C bewerken door Uitkomst en Kosten toe te voegen via de sidebar
    [Tags]    tc_reg_zaken_07
    Go To    /team-c/petitions
    Click Row In Table    table tbody tr.table-row-clickable    0

    Take Screenshot    fullPage=True
    Click    h2 >> text=Uitkomst >> .. >> a.petition-property__edit
    ${options}    Create List
    ...    Gegrond
    FOR    ${option}    IN    @{options}
        ${checkbox}    Get Element By Role    checkbox    name=${option}    exact=True
        Check Checkbox    ${checkbox}
    END

    ${save}    Get Element By Role    button    name=Opslaan
    Click    ${save}

    Get Text    text=Gegrond zijn aangevinkt
    Get Element By Role    heading    name=Binnen/buiten termijn
    Get Element By Role    heading    name=Dictum
    Get Element By Role    heading    name=Doorzending
    Get Element By Role    heading    name=Zwaarte

Nieuw Besluit Koppelen Aan Zaak Team WJZ
    [Documentation]    Nieuw besluit koppelen aan zaak Team WJZ
    [Tags]    tc_reg_zaken_08
    Go To    /wjz-bb/petitions
    Click Row In Table    table tbody tr.table-row-clickable    0
    Get Title    contains    Zaakoverzicht van

    ${link}    Get Element By Role    link    name=Nieuw besluit
    Click    ${link}

    Type Text    id=name    Testbesluit
    Type Text    id=reference    REF1337
    ${date}    Get Current Date    result_format=${RESULT_FORMAT}
    Type Text    id=date    ${date}
    Select Options By    select#type    value    chat

    ${button}    Get Element By Role    button    name=Aanmaken
    Click    ${button}
    Check For Notification    Opgeslagen

    ${element}    Get Table Cell Element    text=Gekoppelde zaken >> .. >> table    -2    1
    Click    ${element} >> a

    Get Title    contains    Zaakoverzicht van
    Get Element By Role    rowheader    name=Testbesluit

Bestaand Besluit Koppelen Aan Zaak
    [Documentation]    Bestaand besluit koppelen aan Zaak
    [Tags]    tc_reg_zaken_09
    Depends On Test    Nieuw Besluit Koppelen Aan Zaak Team WJZ
    Go To    /wjz-bb/petitions
    Click Row In Table    table tbody tr.table-row-clickable    1

    ${link}    Get Element By Role    link    name=Besluit koppelen
    Click    ${link}

    Get Element By Role    heading    name=Besluit koppelen
    Type Text    id=reference    REF1337
    ${button}    Get Element By Role    button    name=Besluit koppelen
    Click    ${button}

    Get Title    contains    Zaakoverzicht van
    Get Element By Role    rowheader    name=Testbesluit

Gekoppeld Besluit Ontkoppelen In Team WJZ
    [Documentation]    Gekoppeld besluit ontkoppelen in Team WJZ
    [Tags]    tc_reg_zaken_10
    Depends On Test    Nieuw besluit koppelen aan zaak Team WJZ

    Go To    /wjz-bb/petitions
    Click Row In Table    table tbody tr.table-row-clickable    0

    Get Element By Role    rowheader    name=Testbesluit

    ${element}    Get Table Cell Element    text=Besluiten >> .. >> table    -1    1
    Click    ${element} >> a

    Get Element By Role    heading    name=Actie bevestigen
    ${button}    Get Element By Role    button    name=Ja
    Click    ${button}

    Get Text    text=Testbesluit is ontkoppeld
    Get Text    text=Er zijn geen besluiten gevonden.

# todo: substatus 'Toebedeling' en 'Intake' bestaan niet?
# oplossing: maak nieuwe item aan, dan weet je wat de default is, verander die substatus naar iets anders, en check of het is veranderd is en op de timeline verschijnt

Substatus Handmatig Wijzigen Van 'Toebedeling' Naar 'Intake'
    [Documentation]    Substatus handmatig wijzigen van 'Toebedeling' naar 'Intake'
    [Tags]    tc_reg_zaken_11
    Go To    /wjz-bb/petitions
    ${button}    Get Element By Role    link    name=Bezwaar aanmaken
    Click    ${button}

    Get Element By Role    heading    name=Nieuwe zaak

    ${combobox}    Get Element By Role    combobox    name=Categorie
    Select Options By    ${combobox}    index    1

    ${button}    Get Element By Role    button    name=Aanmaken
    Click    ${button}
    Check For Notification    Opgeslagen

    ${default_substatus}    Get Text    span.tag.tag--substatus
    ${default_substatus}    Strip String    ${default_substatus}

    ${link}    Get Element By Role    link    name=${default_substatus}
    Click    ${link}

    ${combobox}    Get Element By Role    combobox    name=Status
    Get Selected Options    ${combobox}    label    equals    ${default_substatus}

    Select Options By    ${combobox}    index    2
    ${button}    Get Element By Role    button    name=Opslaan
    Click    ${button}
    Check For Notification    Opgeslagen

    ${new_substatus}    Get Text    span.tag.tag--substatus
    Should Not Be Equal    ${new_substatus}    ${default_substatus}

Notitie Met Bestand Uploaden Aan Zaak In Team C
    [Documentation]    Notitie met bestand uploaden aan zaak in Team C
    [Tags]    tc_reg_zaken_12
    Go To    /team-c/petitions
    Click Row In Table    table tbody tr.table-row-clickable    0

    ${button}    Get Element By Role    link    name=Notitie Toevoegen
    Click    ${button}
    Type Text    id=note    Dit is een testnotitie met een bestand
    ${file_input}    Get Element    input#attachments
    Upload File By Selector    ${file_input}    ${CURDIR}/../resources/test.png
    ${button}    Get Element By Role    button    name=Opslaan
    Click    ${button}

    Get Text    div.timeline-item__note >> text=Dit is een testnotitie met een bestand
    Get Text    a.timeline-item__attachment-link >> text=test.png
    # only check for notes with attachments, because there could be more notes such as system notes
    Get Element Count    li.timeline-item__attachment    equals    1

Twee Zaken Aan Een Zaak Koppelen In Team WJZ
    [Documentation]    Twee zaken aan een zaak koppelen in Team WJZ
    [Tags]    tc_reg_zaken_13
    Go To    /wjz-bb/petitions
    ${references}    Create List
    ${reference1}    Select Reference From Table    table    1
    ${reference2}    Select Reference From Table    table    2
    Append To List    ${references}    ${reference1}    ${reference2}

    Click Row In Table    table tbody tr    0

    FOR    ${reference}    IN    @{references}
        ${link}    Get Element By Role    link    name=Bestaande zaak koppelen
        Click    ${link}

        Get Element By Role    heading    name=Zaak koppelen
        Type Text    id=number    ${reference}
        ${button}    Get Element By Role    button    name=Zaak koppelen
        Click    ${button}

        Get Title    contains    Zaakoverzicht van
        Get Element By Role    rowheader    name=${reference}
    END

Gekoppelde Zaak Ontkoppelen In Team WJZ
    [Documentation]    Gekoppelde zaak ontkoppelen in Team WJZ
    [Tags]    tc_reg_zaken_14
    Depends On Test    Twee Zaken Aan Een Zaak Koppelen In Team WJZ

    ${reference}    Select Reference From Table    h2 >> text=Gekoppelde zaken >> .. >> table    1
    ${element}    Get Table Cell Element    text=Gekoppelde zaken >> .. >> table    -1    1
    Click    ${element} >> a

    Get Element Count    text="${reference}"    equals    0


*** Keywords ***
Setup
    [Documentation]    Setup for the Petitions test suite.
    Seed Database
    Set Browser Timeout    ${BROWSER_TIMEOUT}

    ${running_in_container}    Running In Container
    IF    ${running_in_container}
        Set Suite Variable    ${RESULT_FORMAT}    %m/%d/%Y
    END

    Open App
    Login As User    admin@minvws.nl    admin

Extract And Convert Table Date
    [Documentation]    Extracts a date from a table cell and converts it to the desired format.
    [Arguments]    ${table}    ${column_name}    ${row_index}
    ${cell}    Get Table Cell Element    ${table}    ${column_name}    ${row_index}
    ${text}    Get Text    ${cell}
    ${date}    Convert Date    ${text}    date_format=%d-%m-%Y    result_format=${RESULT_FORMAT}
    RETURN    ${date}

Click Row In Table
    [Documentation]    Clicks a row in a table based on the row index (0-based).
    [Arguments]    ${selector}    ${index}
    ${rows}    Get Elements    ${selector}
    Click    ${rows}[${index}] >> a.icon-only
    Get Title    contains    Zaakoverzicht van

Select Reference From Table
    [Documentation]    Selects a reference from the table based on the row index (1-based).
    [Arguments]    ${table}    ${row_index}
    ${cell}    Get Table Cell Element    ${table}    "Kenmerk"    ${row_index}
    ${text}    Get Text    ${cell}
    RETURN    ${text}
