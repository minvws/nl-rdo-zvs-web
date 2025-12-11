*** Settings ***
Documentation       Suite for testing export functionality in Zaakvolgsysteem.

Resource            ../resources/Setup.resource
Library             Collections
Library             DependencyLibrary
Library             ExcelLibrary

Suite Setup         Setup
Suite Teardown      Logout


*** Test Cases ***
Create Internal Export For 'Bezwaarprocedure' In Team WJZ
    [Documentation]    Create an internal export for 'Bezwaarprocedure' in Team WJZ.
    [Tags]    tc_export_01

    Go To    ${BASE_URL}/wjz-bb/exports

    Take Screenshot    fullPage=True

    Select Options By    select#export-type    label    Intern
    Select Options By    select#petition-type    label    Bezwaar
    ${selector}    Get Element    id=date-from
    Type Date    ${selector}    01-06-2025
    ${selector}    Get Element    id=date-to
    Type Date    ${selector}    10-07-2025
    Click    text=Export maken

    ${row}    Select Export Row From Table    "01-06-2025"

    ${download_url}    Get Attribute    ${row} >> "Export downloaden" >> ..    href
    ${download_info}    Download    ${download_url}    export-10-07-2025.xlsx

    Open Excel Document    ${download_info}[saveAs]    export-internal-report
    ${actual}    Read Excel Column    6    1    10    petition_sheet
    List Should Contain Value    ${actual}    Bezwaar
    List Should Not Contain Value    ${actual}    Beroep
    List Should Not Contain Value    ${actual}    Woo verzoek

Delete Internal Export
    [Documentation]    Delete an internal export.
    [Tags]    tc_export_02

    Depends On Test    Create Internal Export For 'Bezwaarprocedure' In Team WJZ

    Go To    ${BASE_URL}/wjz-bb/exports

    ${row}    Select Export Row From Table    "01-06-2025"

    Click    ${row} >> "Export verwijderen" >> ..

    # todo: this takes too long (10s)
    Run Keyword And Expect Error    *    Select Export Row From Table    "01-06-2025"

Create Rijksdashboard Export For 'Bezwaarprocedure' In Team WJZ
    [Documentation]    Create a Rijksdashboard export for 'Bezwaarprocedure' in Team WJZ.
    [Tags]    tc_export_03

    Go To    ${BASE_URL}/wjz-bb/exports

    Take Screenshot    fullPage=True

    Select Options By    select#export-type    label    Rijksdashboard
    Select Options By    select#petition-type    label    Beroep
    ${selector}    Get Element    id=date-from
    Type Date    ${selector}    31-12-2024
    ${selector}    Get Element    id=date-to
    Type Date    ${selector}    11-07-2025

    Take Screenshot    fullPage=True

    Click    text=Export maken

    ${row}    Select Export Row From Table    "31-12-2024"

    ${download_url}    Get Attribute    ${row} >> "Export downloaden" >> ..    href
    ${download_info}    Download    ${download_url}    export-11-07-2025.xlsx

    Open Excel Document    ${download_info}[saveAs]    export-dashboard-report
    ${sheet_names}    Get List Sheet Names
    List Should Contain Value    ${sheet_names}    beroepen

    # todo: check the content of the sheet

Delete Rijksdashboard Export
    [Documentation]    Delete a Rijksdashboard export.
    [Tags]    tc_export_04
    Depends On Test    Create Rijksdashboard Export For 'Bezwaarprocedure' In Team WJZ

    Go To    ${BASE_URL}/wjz-bb/exports

    ${row}    Select Export Row From Table    "31-12-2024"

    Click    ${row} >> "Export verwijderen" >> ..

    # todo: this takes too long (10s)
    Run Keyword And Expect Error    *    Select Export Row From Table    "31-12-2024"


*** Keywords ***
Setup
    [Documentation]    Setup for export tests.
    Seed Database
    Set Browser Timeout    ${BROWSER_TIMEOUT}
    New Context    acceptDownloads=True
    Open App
    Input Credentials    admin@minvws.nl    admin
    Input Token Code    123456
    Is Logged In

Select Export Row From Table
    [Documentation]    Selects an export from the table based on the date.
    [Arguments]    ${from_date}
    ${element}    Get Table Cell Element    table    text="Datum vanaf"    text=${from_date}
    RETURN    ${element} >> ..
