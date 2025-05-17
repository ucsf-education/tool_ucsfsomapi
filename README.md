
# tool_ucsfsomapi

An admin tool plugin for Moodle that provides a set of custom API endpoints used for reporting
in the UCSF School of Medicine (SOM).

# Installation

## Install with git
* use a command line interface of your choice on the destination system (server with moodle installation)
* switch to the Moodle `admin/tool` folder: `cd /path/to/moodle/admin/tool/`
* `git clone https://github.com/ucsf-ckm/tool_ucsfsomapi.git ucsfsomapi`
* navigate on your Moodle page to admin --> notifications and follow the instructions

## Install from zip
* download zip file from github: https://github.com/ucsf-ckm/tool_ucsfsomapi/archive/master.zip
* unpack zip file to `/path/to/moodle/admin/tool/`
* rename directory `tool_ucsfsomapi` to `ucsfsomapi`
* navigate on your moodle page to admin --> notifications and follow the instructions

# API Endpoints

API entry point: https://{your.moodle.site}/webservice/rest/server.php

<details>
<summary><h2>Get courses for given course-categories</h2></summary>

**Expected input:**

- `ws-token` ... your API token
- `categoryids` ... one or more course category ids
- `ws-function` ... `tool_ucsfsomapi_get_courses`
- `moodlewsrestformat` ... `json` or `xml`

**Example:**

`curl -F "wstoken=XXXXXXXXXXXXXXXXXXXXXXXXXX" -F "wsfunction=tool_ucsfsomapi_get_courses" -F "moodlewsrestformat=json" -F "categoryids[]=309" -F "categoryids[]=310"  -X POST https://{your.moodle.site}/webservice/rest/server.php`

</details>

<details>
<summary><h2>Get quizzes for given courses</h2></summary>

**Expected input:**

- `ws-token` ... your API token
- `courseids` ... one or more course ids
- `ws-function` ... `tool_ucsfsomapi_get_quizzes`
- `moodlewsrestformat` ... `json` or `xml`

**Example:**

`curl -F "wstoken=XXXXXXXXXXXXXXXXXXXXXXXXXX" -F "wsfunction=tool_ucsfsomapi_get_quizzes" -F "moodlewsrestformat=json" -F "courseids[]=1014" -X POST https://{your.moodle.site}/webservice/rest/server.php`

</details>

<details>
<summary><h2>Get questions for given quizzes</h2></summary>

**Expected input:**

- `ws-token` ... your API token
- `quizids` ... one or more quiz ids
- `ws-function` ... `tool_ucsfsomapi_get_questions`
- `moodlewsrestformat` ... `json` or `xml`

**Example:**

`curl -F "wstoken=XXXXXXXXXXXXXXXXXXXXXXXXXX" -F "wsfunction=tool_ucsfsomapi_get_questions" -F "moodlewsrestformat=json" -F "quizids[]=18363" -F "quizids[]=19139" -X POST https://{your.moodle.site}/webservice/rest/server.php`

</details>

<details>
<summary><h2>Get quiz attempts for given quizzes</h2></summary>

**Expected input:**

- `ws-token` ... your API token
- `quizids` ... one or more quiz ids
- `ws-function` ... `tool_ucsfsomapi_get_attempts`
- `moodlewsrestformat` ... `json` or `xml`

**Example:**

`curl -F "wstoken=XXXXXXXXXXXXXXXXXXXXXXXXXX" -F "wsfunction=tool_ucsfsomapi_get_attempts" -F "moodlewsrestformat=json" -F "quizids[]=18363" -F "quizids[]=19139" -X POST https://{your.moodle.site}/webservice/rest/server.php`

</details>

<details>
<summary><h2>Get users by ids</h2></summary>

**Expected input:**

- `ws-token` ... your API token
- `userids` ... one or more user ids
- `ws-function` ... `tool_ucsfsomapi_get_users`
- `moodlewsrestformat` ... `json` or `xml`

**Example:**

`curl -F "wstoken=XXXXXXXXXXXXXXXXXXXXXXXXXX" -F "wsfunction=tool_ucsfsomapi_get_users" -F "moodlewsrestformat=json" -F "userids[]=34345" -F "userids[]=32728" -X POST https://{your.moodle.site}/webservice/rest/server.php`

</details>

<details>
<summary><h2>Mark a question attempt by its id</h2></summary>

**Expected input:**

- `ws-token` ... your API token
- `ws-function` ... `tool_ucsfsomapi_set_question_attempt_mark`
- `moodlewsrestformat` ... `json` or `xml`
- `attemptid` (Required) ... The question attempt id to set mark (int)
- `mark` (Required) ... Mark for this question attempt (string)
- `comment` (Optional, defaults to "null") ... Grader's comment (string)

**Example:**

```bash
curl -F "wstoken=XXXXXXXXXXXXXXXXXXXXXXXXXX" \
     -F "wsfunction=tool_ucsfsomapi_set_question_attempt_mark" \
     -F "moodlewsrestformat=json" \
     -F "attemptid=12345" \
     -F "mark=1.0" \
     -F "comment=Good work" \
     -X POST https://{your.moodle.site}/webservice/rest/server.php
```

**Response:**

An `int` value like `0 => OK`, `1 => FAILED` as defined in [lib/grade/constants.php](https://github.com/ucsf-education/moodle/blob/UCSFCLE_404_STABLE/lib/grade/constants.php#L98)

```
/**
 * GRADE_UPDATE_OK - Grade updated completed successfully.
 */
define('GRADE_UPDATE_OK', 0);

/**
 * GRADE_UPDATE_FAILED - Grade updated failed.
 */
define('GRADE_UPDATE_FAILED', 1);
```

**Error exception examples:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<EXCEPTION class="invalid_parameter_exception">
    <MESSAGE>Invalid parameter value detected</MESSAGE>
    <DEBUGINFO></DEBUGINFO>
</EXCEPTION>

<?xml version="1.0" encoding="UTF-8"?>
<EXCEPTION class="moodle_exception">
    <MESSAGE>No such attempt ID exists</MESSAGE>
    <DEBUGINFO></DEBUGINFO>
</EXCEPTION>

<?xml version="1.0" encoding="UTF-8"?>
<EXCEPTION class="moodle_exception">
    <MESSAGE>Attempt has not closed yet</MESSAGE>
    <DEBUGINFO></DEBUGINFO>
</EXCEPTION>

<?xml version="1.0" encoding="UTF-8"?>
<EXCEPTION class="moodle_exception">
    <MESSAGE>Sorry, but you do not currently have permissions to do that (Grade quizzes manually).</MESSAGE>
    <DEBUGINFO></DEBUGINFO>
</EXCEPTION>
```

</details>


Copyright (c) 2025 The Regents of the University of California.
