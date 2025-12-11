*** Settings ***
Documentation       Suite for testing management functionalities in Zaakvolgsysteem.

Library             Collections
Library             DependencyLibrary
Library             ../libraries/totp.py
Resource            ../resources/Setup.resource
Resource            ../resources/Mail.resource

Suite Setup         Setup
Test Teardown       Take Screenshot    fullPage=True


*** Test Cases ***
Nieuwe Gebruiker Aanmaken Als Applicatiebeheerder
    [Documentation]    Nieuwe gebruiker aanmaken als applicatiebeheerder en schrijfrechten op Team C, geen rechten op Team WJZ en leesrechten op Team A en B.
    [Tags]    tc_reg_beheer_01
    Create User Account
    ...    tester
    ...    tester@minvws.nl
    ...    admin=True
    ...    team_c_write=True
    ...    team_c_read=True
    ...    team_a_read=True
    ...    team_b_read=True
    Sleep    5s
    ${email}    Search Email    subject:[Zaakvolgsysteem]: Verifieer email-adres
    ${links}    Get Links From Email    ${email}[ID]
    ${link}    Get Activation Link    ${links}

    # todo: there's an issue within the test environment where the link does not work properly in the headless browser
#    Go To    ${link}
#
#    Get Text    text=Wachtwoord resetten
#    Type Text    id=password    new#password#
#    Type Text    id=password_confirmation    new#password#
#    Click    text=Opslaan
#    Logout
#
#    Input Credentials    tester@minvws.nl    new#password#
#    Click    text=Inschakelen
#    ${secret}    Get Attribute    input[type=hidden]    value
#    ${code}    Get Totp    ${secret}
#    Type Text    id=update_otp_otp_confirmation    ${code}
#    Click    text=Opslaan
#    Get Element By Role    heading    name=Profiel
#    Check Create Button Presence    /team-c/petitions    True
#
#    # can see team A and B but cannot edit in team A and B
#    # team WJZ is not accessible
#    Get Element Count    a.department-selector__tag.department-selector__tag--wjz-bb    equals    0
#
#    Check Create Button Presence    /team-a/petitions    False
#    Check Create Button Presence    /team-b/petitions    False

Bestaande Gebruiker Aanpassen Email En Rechten
    [Documentation]    Bestaande gebruiker aanpassen door emailadres te wijzigen en schrijfrechten op Team WJZ te geven.
    [Tags]    tc_reg_beheer_02
    Depends On Test    Nieuwe Gebruiker Aanmaken Als Applicatiebeheerder

    Go To User Access Control    tester@minvws.nl
    Change User Access Control
    ...    tester@minvws.nl
    ...    admin=True
    ...    team_c_write=True
    ...    team_c_read=True
    ...    team_a_read=True
    ...    team_b_read=True
    ...    team_wjz_write=True
    ...    team_wjz_read=True

    Type Text    id=name    tester2
    Type Text    id=email    tester2@minvws.nl

    ${button}    Get Element By Role    button    name=Opslaan
    Click    ${button}
    Check For Notification    Opgeslagen

    Get Attribute    id=name    value    equals    tester2
    Get Attribute    id=email    value    equals    tester2@minvws.nl

    ${rights}    Get User Access Checkbox States
    ${expected_rights}    Create Dictionary
    ...    active=${True}
    ...    admin=${True}
    ...    team_a_read=${True}
    ...    team_a_write=${False}
    ...    team_b_read=${True}
    ...    team_b_write=${False}
    ...    team_c_read=${True}
    ...    team_c_write=${True}
    ...    team_wjz_read=${True}
    ...    team_wjz_write=${True}
    Dictionaries Should Be Equal    ${rights}    ${expected_rights}

Zaaksoort Aanmaken Annuleren
    [Documentation]    Zaaksoort aanmaken annuleren
    [Tags]    tc_reg_beheer_03
    Go To    /team-a/admin/petition-types/create
    Get Element By Role    heading    name=Zaaksoort toevoegen
    Type Text    id=name    Zaaksoort Test
    Type Text    id=particularity_label    Label Test
    ${cancel}    Get Element By Role    link    name=Annuleren
    Click    ${cancel}
    Get Element By Role    heading    name=Zaaksoorten

Zaaksoort Aanmaken
    [Documentation]    Zaaksoort aanmaken
    [Tags]    tc_reg_beheer_04
    Go To    /team-a/admin/petition-types/create
    Get Element By Role    heading    name=Zaaksoort toevoegen
    Type Text    id=name    Zaaksoort Test
    Type Text    id=particularity_label    Label Test
    ${save}    Get Element By Role    button    name=Zaaksoort toevoegen
    Click    ${save}
    Get Element By Role    heading    name=Zaaksoorten
    Check For Notification    Opgeslagen
    Get Element Count    th >> text="Zaaksoort Test"    equals    1

Bestaande Zaaksoort Aanpassen
    [Documentation]    Bestaande Zaaksoort aanpassen
    [Tags]    tc_reg_beheer_05
    Go To    /team-a/admin/petition-types
    Click    th >> text="Zaaksoort Test" >> ..
    Get Element By Role    heading    name=Zaaksoort bewerken
    Type Text    id=name    Zaaksoort Test Aangepast
    Type Text    id=particularity_label    Label Test 2
    ${save}    Get Element By Role    button    name=Zaaksoort bewerken
    Click    ${save}
    Get Element By Role    heading    name=Zaaksoorten
    Check For Notification    Opgeslagen
    Get Element Count    th >> text="Zaaksoort Test Aangepast"    equals    1

Feestdag Bewerken Naam Aanpassen
    [Documentation]    Feestdag bewerken. Naam aanpassen van 'Eerste kerstdag'
    [Tags]    tc_reg_beheer_06
    Go To    /admin/public-holidays
    Click    th >> text="Eerste Kerstdag" >> ..
    Get Element By Role    heading    name=Feestdag bewerken
    Type Text    id=name    1e kerstdag
    ${save}    Get Element By Role    button    name=Feestdag bewerken
    Click    ${save}
    Check For Notification    Opgeslagen

Nieuwe Categorie Aanmaken Met Alle Velden
    [Documentation]    Nieuwe Categorie aanmaken met alle velden ingevuld
    [Tags]    tc_reg_beheer_07
    Go To    /team-a/admin/petition-categories/create
    Get Element By Role    heading    name=Categorie toevoegen
    Type Text    id=name    Categorie Test
    ${save}    Get Element By Role    button    name=Categorie toevoegen
    Click    ${save}
    Get Element By Role    heading    name=Categorieën
    Check For Notification    Opgeslagen

Bestaande Categorie Aanpassen
    [Documentation]    Bestaande Categorie aanpassen
    [Tags]    tc_reg_beheer_08
    Go To    /team-a/admin/petition-categories
    ${buttons}    Get Elements    th >> text="Categorie Test" >> .. >> div.actions a
    Click    ${buttons}[0]
    Get Element By Role    heading    name=Categorie bewerken
    Type Text    id=name    Categorie Test Aangepast
    ${save}    Get Element By Role    button    name=Categorie bewerken
    Click    ${save}
    Get Element By Role    heading    name=Categorieën
    Check For Notification    Opgeslagen

Bestaande Categorie Verwijderen
    [Documentation]    Bestaande Categorie verwijderen
    [Tags]    tc_reg_beheer_09
    Go To    /team-a/admin/petition-categories
    ${buttons}    Get Elements    th >> text="Categorie Test Aangepast" >> .. >> div.actions a
    Click    ${buttons}[0]
    Get Element By Role    heading    name=Categorie bewerken
    ${checkbox}    Get Element By Role    checkbox
    Uncheck Checkbox    ${checkbox}

    ${button}    Get Element By Role    button    name=Categorie bewerken
    Click    ${button}
    Check For Notification    Opgeslagen

    Get Text    th >> text="Categorie Test Aangepast" >> .. >> text=Nee


*** Keywords ***
Setup
    [Documentation]    Setup for the management tests.
    Seed Database
    Set Browser Timeout    ${BROWSER_TIMEOUT}
    Open App
    Login As User    admin@minvws.nl    admin
