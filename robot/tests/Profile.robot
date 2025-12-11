*** Settings ***
Documentation       Suite for testing login functionality in Zaakvolgsysteem.

Resource            ../resources/Setup.resource
Resource            ../resources/Mail.resource
Library             String
Library             DependencyLibrary
Library             ../libraries/totp.py

Suite Setup         Setup
Test Setup          Setup For Test
Test Teardown       Logout


*** Variables ***
${RANDOM_PASSWORD}      Generate Random String
...                     128
...                     [LOWER][LETTERS][NUMERS][DIGITS]$#


*** Test Cases ***
Change Existing Password With Incorrect Confirmation
    [Documentation]    Change the existing password using an incorrect password confirmation.
    [Tags]    tc_reg_profile_01
    Change Password    admin    test#password#    wrong#password#
    Get Text    text=De wachtwoordbevestiging is mislukt

Change Password To New Password With 11 Characters
    [Documentation]    Change the password to a new password with 11 characters.
    [Tags]    tc_reg_profile_02
    Change Password    admin    test#pass##    test#pass##
    Get Text    text=Vul op zijn minst 12 tekens in.

Change Password To New Password With 12 Characters
    [Documentation]    Change the password to a new password with 12 characters.
    [Tags]    tc_reg_profile_03
    Change Password    admin    test#password#    test#password#
    Get Title    contains    Inloggen
    Login As User    admin@minvws.nl    test#password#
    Set Suite Variable    ${LATEST_PASSWORD}    test#password#

Change Password To New Password With 128 Characters
    [Documentation]    Change the password to a new password with 128 characters.
    [Tags]    tc_reg_profile_04
    Change Password    ${LATEST_PASSWORD}    ${RANDOM_PASSWORD}    ${RANDOM_PASSWORD}
    Login As User    admin@minvws.nl    ${RANDOM_PASSWORD}
    Set Suite Variable    ${LATEST_PASSWORD}    ${RANDOM_PASSWORD}

Disable And Enable 2FA
    [Documentation]    Disable and enable two-factor authentication (2FA).
    [Tags]    tc_reg_profile_05
    ${button}    Get Element By Role    button    name=Opnieuw instellen
    Click    ${button}
    Login As User    admin@minvws.nl    ${LATEST_PASSWORD}    ${False}
    Click    text=Inschakelen
    ${secret}    Get Attribute    input[type=hidden]    value
    ${code}    Get Totp    ${secret}
    Type Text    id=update_otp_otp_confirmation    ${code}
    ${button}    Get Element By Role    button    name=Opslaan
    Click    ${button}
    Is Logged In


*** Keywords ***
Setup
    [Documentation]    Setup for the profile tests.
    Seed Database
    Set Browser Timeout    ${BROWSER_TIMEOUT}
    Set Suite Variable    ${LATEST_PASSWORD}    admin
    Open App

Setup For Test
    [Documentation]    Setup for the test case.
    Login As User    admin@minvws.nl    ${LATEST_PASSWORD}
    Go To    /profile

Change Password
    [Documentation]    Change the password.
    [Arguments]    ${password}    ${new_password}    ${confirm_password}
    Type Text    id=update_password_current_password    ${password}
    Type Text    id=update_password_password    ${new_password}
    Type Text    id=update_password_password_confirmation    ${confirm_password}
    ${buttons}    Get Elements    button >> text=Opslaan
    Click    ${buttons}[1]
