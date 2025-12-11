*** Settings ***
Documentation       Suite for testing contacts management in Zaakvolgsysteem.

Resource            ../resources/Setup.resource
Library             Collections

Suite Setup         Setup
Suite Teardown      Logout


*** Test Cases ***
Create New Contact With All Fields Filled
    [Documentation]    Nieuw contact aanmaken met alle velden ingevuld.
    [Tags]    tc_reg_contact_01
    Go To    ${BASE_URL}/team-a/contacts
    Click    text=Contact toevoegen
    Select Options By    select#contact-type    label    Juridisch specialist
    Type Text    id=last_name    de Tester
    Type Text    id=organisation_name    Robocop
    Click    button >> text=Contact toevoegen
    Check For Notification    Opgeslagen
    Get Element By    text    de Tester
    Get Element By    text    Robocop

Edit Contact By Clearing Last Name And Organization
    [Documentation]    Contact bewerken door achternaam en organisatie te wissen.
    [Tags]    tc_reg_contact_02
    Open First Contact For Editing
    Clear Text    id=last_name
    Clear Text    id=organisation_name
    Click    button >> text=Contact bewerken
    Get Text    text=Het veld Achternaam is verplicht wanneer Organisatie niet ingevuld is.
    Get Text    text=Het veld Organisatie is verplicht wanneer Achternaam niet ingevuld is.

Edit Contact By Setting 'Type Contact' To 'Media' And Reopening
    [Documentation]    Contact bewerken door 'Type contact' op 'Media' te zetten en opnieuw openen.
    [Tags]    tc_reg_contact_03
    Open First Contact For Editing
    Select Options By    select#contact-type    label    Media
    Click    button >> text=Contact bewerken
    Check For Notification    Opgeslagen
    Get Element By    text    Media

Edit Contact By Clearing Last Name
    [Documentation]    Contact bewerken door achternaam te wissen.
    [Tags]    tc_reg_contact_04
    Open First Contact For Editing
    ${last_name}    Get Text    id=last_name
    Clear Text    id=last_name
    Type Text    id=organisation_name    Robocop Corp    # make sure to not have empty last name and organisation
    Click    button >> text=Contact bewerken
    Check For Notification    Opgeslagen
    Go To    ${BASE_URL}/team-a/contacts
    Get Element Count    text=${last_name}    equals    0
    Get Element Count    text=Robocop Corp    equals    1

Cancel Contact Creation
    [Documentation]    Contact aanmaken annuleren.
    [Tags]    tc_reg_contact_05
    Go To    ${BASE_URL}/team-a/contacts
    ${count}    Get Element Count    tr.table-row-clickable
    Click    text=Contact toevoegen
    Click    text=Annuleren
    ${count2}    Get Element Count    tr.table-row-clickable
    Should Be Equal    ${count}    ${count2}


*** Keywords ***
Setup
    [Documentation]    Setup for export tests.
    Seed Database
    Set Browser Timeout    ${BROWSER_TIMEOUT}
    Open App
    Input Credentials    admin@minvws.nl    admin
    Input Token Code    123456
    Is Logged In

Open First Contact For Editing
    [Documentation]    Opens the first contact for editing.
    Go To    ${BASE_URL}/team-a/contacts
    ${rows}    Get Elements    tr.table-row-clickable
    ${row}    Get From List    ${rows}    0
    Click    ${row}
    Click    text=Contact bewerken
