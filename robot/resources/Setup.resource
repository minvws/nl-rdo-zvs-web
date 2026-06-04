*** Settings ***
Documentation       Setup for Zaakvolgsysteem tests.

Library             Collections
Library             Process
Library             Browser
Library             DateTime
Library             OperatingSystem
Library             String


*** Variables ***
${BASE_URL}             %{BASE_URL=127.0.0.1}
${BROWSER_TIMEOUT}      %{BROWSER_TIMEOUT=3s}


*** Keywords ***
Running In Container
    [Documentation]    Returns True if the tests are running in a Docker container.
    ${running_in_container}=    Get Environment Variable    RUNNING_IN_CONTAINER    ${False}
    ${running_in_container}=    Convert To Boolean    ${RUNNING_IN_CONTAINER}
    RETURN    ${running_in_container}

Get Container Name
    [Documentation]    Gets the name of the Docker container running the application.
    ${result}=    Run Process
    ...    docker
    ...    ps
    ...    --filter
    ...    name\=laravel.robot
    ...    --format
    ...    "{{.Names}}"
    Should Be Equal As Integers    ${result.rc}    0
    ${container_name}=    Strip String    ${result.stdout}
    ${container_name}=    Replace String    ${container_name}    \"    ${EMPTY}
    Should Not Be Empty    ${container_name}
    RETURN    ${container_name}

Get Container ID
    [Documentation]    Gets the ID of the Docker container running the application.
    ${result}=    Run Process
    ...    docker
    ...    ps
    ...    --filter
    ...    name\=laravel.robot
    ...    --format
    ...    "{{.ID}}"
    Should Be Equal As Integers    ${result.rc}    0
    ${container_id}=    Strip String    ${result.stdout}
    ${container_id}=    Replace String    ${container_id}    \"    ${EMPTY}
    Should Not Be Empty    ${container_id}
    RETURN    ${container_id}

Seed Database
    [Documentation]    Seeds the database with initial data.
    ${container_id}=    Get Container ID

    ${result}=    Run Process
    ...    docker
    ...    exec
    ...    ${container_id}
    ...    php
    ...    artisan
    ...    migrate:fresh
    ...    --seed
    Log    ${result.rc}
    Log    ${result.stdout}
    Log    ${result.stderr}
    Should Be Equal As Integers    ${result.rc}    0

Open App
    [Documentation]    Opens the browser to the base URL.
    ${HEADLESS}=    Get Variable Value    ${HEADLESS}    ${True}
    VAR    @{list}    --disable-web-security    --allow-running-insecure-content    --ignore-certificate-errors
    New Browser    chromium    headless=${HEADLESS}    args=${list}    slowMo=1
    New Context
    ...    baseURL=${BASE_URL}
    ...    acceptDownloads=True
    ...    locale=nl-NL
    ...    timezoneId=Europe/Amsterdam
    ...    bypassCSP=True
    ...    ignoreHTTPSErrors=True
    New Page    /
    Get Title    contains    Zaakvolgsysteem

Input Credentials
    [Documentation]    Logs in with the provided username and password.
    [Arguments]    ${username}    ${password}
    Type Text    id=email    ${username}
    Type Text    id=password    ${password}
    ${button}=    Get Element By Role    button    name=Inloggen
    Click    ${button}

Input Token Code
    [Documentation]    Inputs the token code on the token page.
    [Arguments]    ${code}=123456
    Type Text    id=code    ${code}
    ${button}=    Get Element By Role    button    name=Verzenden
    Click    ${button}

Is Logged In
    [Documentation]    Checks if the user is logged in by verifying the presence of the logout button.
    Get Text    button.logout-button    equals    Uitloggen

Login As User
    [Documentation]    Logs in the user with the provided credentials and token code.
    [Arguments]    ${username}    ${password}    ${code}=123456
    Input Credentials    ${username}    ${password}
    IF    ${code}    Input Token Code    ${code}

Logout
    [Documentation]    Logs out the user.
    ${button}=    Get Element By Role    button    name=Uitloggen
    Click    ${button}

Go To User Access Control
    [Documentation]    Navigates to the user access control page for the given username.
    [Arguments]    ${username}
    Go To    ${BASE_URL}/admin/users
    Get Text    text=Gebruikers
    Take Screenshot    fullPage=True
    Click    tr.table-row-clickable >> text=${username} >> .. >> a

Create User Account
    [Documentation]    Creates a new user account with the given username and password.
    [Arguments]    ${username}
    ...    ${email}
    ...    ${admin}=False
    ...    ${team_a_read}=False
    ...    ${team_a_write}=False
    ...    ${team_b_read}=False
    ...    ${team_b_write}=False
    ...    ${team_c_read}=False
    ...    ${team_c_write}=False
    ...    ${team_wjz_read}=False
    ...    ${team_wjz_write}=False
    Go To    ${BASE_URL}/admin/users/create
    Type Text    id=name    ${username}
    Type Text    id=email    ${email}
    Set User Access Checkboxes
    ...    ${admin}
    ...    ${team_a_write}
    ...    ${team_a_read}
    ...    ${team_b_write}
    ...    ${team_b_read}
    ...    ${team_c_write}
    ...    ${team_c_read}
    ...    ${team_wjz_write}
    ...    ${team_wjz_read}
    Click    button[type="submit"] >> text=Gebruiker toevoegen
    Check For Notification    Opgeslagen

Change User Access Control
    [Documentation]    Navigates to the access control page.
    [Arguments]    ${username}
    ...    ${admin}=False
    ...    ${team_a_read}=False
    ...    ${team_a_write}=False
    ...    ${team_b_read}=False
    ...    ${team_b_write}=False
    ...    ${team_c_read}=False
    ...    ${team_c_write}=False
    ...    ${team_wjz_read}=False
    ...    ${team_wjz_write}=False
    Go To User Access Control    ${username}

    Set User Access Checkboxes
    ...    ${admin}
    ...    ${team_a_write}
    ...    ${team_a_read}
    ...    ${team_b_write}
    ...    ${team_b_read}
    ...    ${team_c_write}
    ...    ${team_c_read}
    ...    ${team_wjz_write}
    ...    ${team_wjz_read}
    Click    text=Opslaan

Set User Access Checkboxes
    [Documentation]    Sets the user access checkboxes based on the provided arguments.
    [Arguments]    ${admin}=False
    ...    ${team_a_write}=False
    ...    ${team_a_read}=False
    ...    ${team_b_write}=False
    ...    ${team_b_read}=False
    ...    ${team_c_write}=False
    ...    ${team_c_read}=False
    ...    ${team_wjz_write}=False
    ...    ${team_wjz_read}=False
    ${checkboxes}=    Get Elements    div.checkbox input[type="checkbox"]
    VAR    @{states}=
    ...    True    # active user
    ...    ${admin}
    ...    ${team_a_write}
    ...    ${team_a_read}
    ...    ${team_b_write}
    ...    ${team_b_read}
    ...    ${team_c_write}
    ...    ${team_c_read}
    ...    ${team_wjz_write}
    ...    ${team_wjz_read}
    FOR    ${index}    ${state}    IN ENUMERATE    @{states}
        IF    ${state}
            Check Checkbox    ${checkboxes}[${index}]
        ELSE
            Uncheck Checkbox    ${checkboxes}[${index}]
        END
    END

    Take Screenshot    fullPage=True

Get User Access Checkbox States
    [Documentation]    Returns a dictionary with the states of the user access checkboxes.
    ${checkboxes}=    Get Elements    div.checkbox input[type="checkbox"]
    VAR    @{names}=
    ...    active
    ...    admin
    ...    team_a_write
    ...    team_a_read
    ...    team_b_write
    ...    team_b_read
    ...    team_c_write
    ...    team_c_read
    ...    team_wjz_write
    ...    team_wjz_read
    VAR    &{result}=    &{EMPTY}
    FOR    ${index}    ${name}    IN ENUMERATE    @{names}
        ${is_checked}=    Get Checkbox State    ${checkboxes}[${index}]
        Set To Dictionary    ${result}    ${name}=${is_checked}
    END
    RETURN    ${result}

Check Create Button Presence
    [Documentation]    Checks for the presence of the create button on the given URL.
    [Arguments]    ${url}    ${should_have_create_button}
    Go To    ${url}
    Get Element By Role    heading    name=Zaken
    IF    ${should_have_create_button}
        Get Element Count    id=petitions-create-beroep    greater than    0
    ELSE
        Get Element Count    id=petitions-create-beroep    equals    0
    END

Check For Notification
    [Documentation]    Checks for a notification with the given text and closes it.
    [Arguments]    ${text}
    Get Text    div.alert    contains    ${text}
    ${close_button}    Get Element Count    div.alert button[aria-label="Sluiten"]
    IF    ${close_button} > 0
        Click    div.alert button[aria-label="Sluiten"]
    END

Type Date
    [Documentation]    Types a date into a date input field.
    [Arguments]    ${selector}    ${date}    ${date_format}=%d-%m-%Y

    ${running_in_container}=    Running In Container
    IF    ${running_in_container}
        ${result_format}=    Set Variable    %m/%d/%Y
    ELSE
        ${result_format}=    Set Variable    %d/%m/%Y
    END
    ${date}=    Convert Date    ${date}    result_format=${result_format}    date_format=${date_format}

    Type Text    ${selector}    ${date}
