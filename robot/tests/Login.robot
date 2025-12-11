*** Settings ***
Documentation       Suite for testing login functionality in Zaakvolgsysteem.

Resource            ../resources/Setup.resource
Resource            ../resources/Mail.resource
Library             DependencyLibrary

Suite Setup         Setup


*** Test Cases ***
Login With Valid Username And Password
    [Documentation]    Login with a valid username and password.
    [Tags]    tc_reg_login_01
    Input Credentials    admin@minvws.nl    admin
    Input Token Code    123456
    Is Logged In
    [Teardown]    Logout

Login With Incorrect Password
    [Documentation]    Attempt to login with a valid username and incorrect password.
    [Tags]    tc_reg_login_02
    Input Credentials    admin@minvws.nl    wrongpassword
    Get Text    div[role="alert"]    equals    Login gegevens zijn incorrect. Probeer het nog een keer.

Login With Incorrect Username
    [Documentation]    Attempt to login with an incorrect username and valid password.
    [Tags]    tc_reg_login_03
    Input Credentials    wronguser@minvws.nl    admin
    Get Text    div[role="alert"]    equals    Login gegevens zijn incorrect. Probeer het nog een keer.

Create New Password Via Forgot Password Link
    [Documentation]    Create a new password using the 'Forgot password' link.
    [Tags]    tc_reg_login_04
    Open App
    Click    "Wachtwoord vergeten?"
    Type Text    id=email    admin@minvws.nl
    Click    button
    ${email}    Search Email    subject:[Zaakvolgsysteem]: Herstel wachtwoord
    ${links}    Get Links From Email    ${email}[ID]
    ${link}    Get Reset Password Link    ${links}
    Go To    ${link}
    Get Text    text=Wachtwoord resetten

    Type Text    id=password    new#password#
    Type Text    id=password_confirmation    new#password#
    Click    button
    Get Text    text=Bevestiging: Je wachtwoord is succesvol hersteld

Login With New Password And Logout
    [Documentation]    Login with the new password and logout.
    [Tags]    tc_reg_login_05
    Depends On Test    Create New Password Via Forgot Password Link
    Input Credentials    admin@minvws.nl    new#password#
    Input Token Code    123456
    Is Logged In
    Logout
    Get Text    text=Inloggen


*** Keywords ***
Setup
    [Documentation]    Setup for login tests.
    Seed Database
    Set Browser Timeout    ${BROWSER_TIMEOUT}
    Open App
