# Temporal Convector - User Guide

## Overview

Temporal Convector normalizes OfflineQuiz output so every group ends up with the same page count.

It is useful when:

- groups have different question-page layouts
- all printed copies must be physically aligned
- correction packages must follow the same pagination logic

## What the plugin produces

For one OfflineQuiz activity, the plugin can generate a ZIP archive containing:

- normalized questionnaires
- native OfflineQuiz answer sheets
- normalized correction forms

## Access

Two capabilities control the feature:

- `local/offlinequizaddons:view`
- `local/offlinequizaddons:generate`

Users with view access can inspect the analysis page. Users with generate access can also download the archive.

## Typical workflow

1. Open the OfflineQuiz activity.
2. Open the Temporal Convector page from the plugin navigation entry.
3. Review the group analysis table.
4. Inspect question details if needed.
5. Generate the ZIP archive when normalization is required.

## Analysis screen

The page shows, for each group:

- question count
- current page count
- blank pages required
- final normalized page count

If all groups already match, generation is not proposed.

## Preconditions

Before launching generation:

- the OfflineQuiz activity must exist
- groups must contain questions
- question types must be supported by the current implementation

Currently accepted question types:

- `multichoice`
- `essay`
- `shortanswer`
- `truefalse`

## Error handling

Generation is blocked when:

- the activity cannot be resolved
- no group contains questions
- an unsupported question type is detected
- PDF or ZIP generation fails

In these cases, the page displays Moodle notifications instead of returning partial files.

## Version Context

- Component: `local_offlinequizaddons`
- Moodle target: `4.5+`
- Release line: `1.1.0`
