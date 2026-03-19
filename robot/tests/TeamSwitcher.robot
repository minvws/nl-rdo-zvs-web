*** Settings ***
Documentation       Suite for testing team switching in Zaakvolgsysteem.

Resource            ../resources/Setup.resource
Library             Collections

Suite Setup         Setup
Test Setup          Login As User    admin@minvws.nl    admin
Test Teardown       Logout


*** Variables ***
@{TEAMS}
...         Team A Woo verzoeken regulier
...         Team B Woo verzoeken Corona
...         Team C Bezwaar en Beroep Woo
...         WJZ Afdeling Bezwaar en Beroep


*** Test Cases ***
Create Case With Admin Rights And Write Access For Team B
    [Documentation]    Zaak aanmaken met applicatiebeheerderrechten en alleen schrijfrechten voor Team B.
    [Tags]    tc_reg_afdsw_01
    Change User Access Control    team-b-write@minvws.nl    admin=True    team_b_write=True
    Logout
    Login As User    team-b-write@minvws.nl    admin
    Check Create Button Presence    /team-b/petitions    True
    Click    id=petitions-create-beroep
    Select Options By    id=petition-category-id    index    1    # select random category
    Click    text=Aanmaken
    Check For Notification    Opgeslagen

    ${menu}    Get Element    ul.main-nav__list
    Get Text    ${menu} >> text=Zaken
    Get Text    ${menu} >> text=Contacten
    Get Text    ${menu} >> text=Beheer
    Get Text    ${menu} >> text=Exporteren

Edit Case With Admin Rights And Read + Write Access For Team C
    [Documentation]    Zaak bewerken met applicatiebeheerderrechten en lees + schrijfrechten voor Team C.
    [Tags]    tc_reg_afdsw_02
    Change User Access Control    team-a-write@minvws.nl    admin=False    team_c_read=True
    Change User Access Control    team-b-write@minvws.nl    admin=False    team_c_read=True
    Change User Access Control    team-c-write@minvws.nl    admin=True    team_c_read=True    team_c_write=True

    Logout
    Login As User    team-c-write@minvws.nl    admin
    Open First Case For Editing
    Get Element Count    div.button-container a    greater than    0

    Evaluate JavaScript    div.button-container >> text=Standaard wettelijke termijn    (element) => element.click()
    Click    button >> text=Standaard wettelijke termijn toevoegen
    Check For Notification    Opgeslagen

    Logout
    Login As User    team-a-write@minvws.nl    admin
    Open First Case For Editing
    Get Element Count    div.button-container a    equals    0

    Logout
    Login As User    team-b-write@minvws.nl    admin
    Open First Case For Editing
    Get Element Count    div.button-container a    equals    0

Disable Admin Rights And Verify 'Beheer' Button Is Unavailable
    [Documentation]    Applicatiebeheerrechten uitzetten en controleren of de 'Beheer' button niet meer beschikbaar is.
    [Tags]    tc_reg_afdsw_03
    Change User Access Control    team-a-write@minvws.nl    admin=False    team_a_read=True    team_a_write=True
    Logout
    Login As User    team-a-write@minvws.nl    admin
    Get Element Count    ul.main-nav__list >> text=Beheer    equals    0

Disable Read And Write Access For Teams A And C
    [Documentation]    Lees- en schrijfrechten uitzetten voor Team A en C.
    [Tags]    tc_reg_afdsw_04

    Change User Access Control
    ...    admin@minvws.nl
    ...    admin=True
    ...    team_b_read=True
    ...    team_b_write=True
    ...    team_wjz_read=True
    ...    team_wjz_write=True

    Go To    /team-b/petitions
    Open Department Selector Menu

    Get Element Count    div#department-selector__list a    equals    2
    Get Text    a.department-selector__link >> text=Team B Woo verzoeken Corona
    Get Text    a.department-selector__link >> text=WJZ Afdeling Bezwaar en Beroep

Switch Between Teams A, B, C, And WJZ
    [Documentation]    Switchen tussen Team A, B, C en WJZ.
    [Tags]    tc_reg_afdsw_05

    Change User Access Control
    ...    admin@minvws.nl
    ...    team_a_read=True
    ...    team_a_write=True
    ...    team_b_read=True
    ...    team_b_write=True
    ...    team_c_read=True
    ...    team_c_write=True
    ...    team_wjz_read=True
    ...    team_wjz_write=True
    Go To    /team-a/petitions

    FOR    ${team}    IN    @{TEAMS}
        Open Department Selector Menu
        Click    div.department-selector a.department-selector__link >> text=${team}
        Get Text    div.department-selector a#toggle-element    contains    ${team}
    END


*** Keywords ***
Setup
    [Documentation]    Setup for export tests.
    Seed Database
    Set Browser Timeout    ${BROWSER_TIMEOUT}
    Open App

Open First Case For Editing
    [Documentation]    Opens the first case for editing.
    Go To    /team-c/petitions
    Click    text=Kolommen tonen/verbergen
    ${count}    Get Element Count    button.column-toggle.active >> text=zaaksoort
    IF    ${count} == 1    Click    button.column-toggle >> text=zaaksoort
    Select Options By    select#petition-type    label    Woo verzoek
    ${elements}    Get Elements    tr.table-row-clickable
    ${row}    Get Element    ${elements}[0]
    Click    ${row}

Open Department Selector Menu
    [Documentation]    Opens the department selector.
    Click    div.department-selector a.department-selector__trigger svg
