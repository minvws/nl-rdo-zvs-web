*** Settings ***
Documentation       Helpers for Mailpit API interactions.

Library             Collections
Library             RequestsLibrary
Library             OperatingSystem


*** Variables ***
${MAILPIT_API_BASE_URL}     %{MAILPIT_API_BASE_URL}


*** Keywords ***
Search Email
    [Documentation]    Searches for an email with the specified squery.
    [Arguments]    ${query}
    ${response}=    GET    ${MAILPIT_API_BASE_URL}/api/v1/search    params=query=${query}&limit=1
    Log    ${response.json()}
    VAR    ${message_id}=    ${response.json()}[messages][0][ID]
    ${response}=    GET    ${MAILPIT_API_BASE_URL}/api/v1/message/${message_id}
    RETURN    ${response.json()}

Get Links From Email
    [Documentation]    Extracts links from the email body.
    [Arguments]    ${message_id}
    ${response}=    GET    ${MAILPIT_API_BASE_URL}/api/v1/message/${message_id}/link-check
    Log    ${response.json()}
    RETURN    ${response.json()}[Links]

Get Reset Password Link
    [Documentation]    Finds the reset password link from the list of links.
    [Arguments]    ${links}
    FOR    ${item}    IN    @{links}
        ${url}=    Get From Dictionary    ${item}    URL
        IF    'reset-password' in $url    RETURN    ${url}
    END
    Fail    No reset password link found in links.

Get Activation Link
    [Documentation]    Finds the activation link from the list of links.
    [Arguments]    ${links}
    FOR    ${item}    IN    @{links}
        ${url}=    Get From Dictionary    ${item}    URL
        IF    'verify' in $url    RETURN    ${url}
    END
    Fail    No activation link found in links.
