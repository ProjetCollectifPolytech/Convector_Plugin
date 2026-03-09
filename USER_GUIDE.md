# Temporal Convector - User Guide

## 1. Overview

The **Temporal Convector** is a feature of the OfflineQuiz Addons plugin for Moodle.

Its goal is to **normalize exam copy lengths** across all OfflineQuiz groups so every generated exam copy has the same total number of pages.

This is useful when:
- different groups contain different page counts,
- printing and distribution must stay consistent (to staple the copies together, for example).

---

## 2. What the feature does

For one OfflineQuiz activity, the Temporal Convector:

1. Analyzes all groups and their question page distribution.
2. Detects the group with the highest page count.
3. Calculates how many blank pages must be added to each other group.
4. Generates a ZIP package containing, for each group:
   - a normalized **Question Sheet** PDF,
   - an **Answer Sheet** PDF,
   - a normalized **Correction Form** PDF.

---

## 3. Access and permissions

### Required capability
You must have the Moodle capability:
- `local/offlinequizaddons:view`

By default, it is typically granted to:
- Teacher
- Editing teacher
- Manager

### Where to find the link
After creating an OfflineQuiz activity, make sure to create at least one quiz. Get to the **"Preparation"** tab where you'll find the button **"Download data from Temporal Convector"** that will lead to the Temporal Convector page.

---

## 4. Preconditions

Before running the Temporal Convector, ensure:

- the OfflineQuiz activity exists and is accessible,
- at least one question is present,
- question types are compatible.

Supported question types in the current implementation:
- Multichoices
- Essays
- Shortanswers
- True or False

If the activity is invalid, the page shows an error and generation is blocked.

---

## 5. Step-by-step usage

1. Open your OfflineQuiz activity.
2. Open the **Preparation** tab.
3. Open the **Temporal Convector** page.
4. Review the **Group Analysis** table :
   - Question count
   - Current pages
   - Blank pages needed
   - Final pages
5. Expand **Question Details** to inspect per-question page placement.
6. If normalization is needed, click **Generate Normalized PDFs**.
7. Download the generated ZIP file.

If all groups already have the same number of pages, the page shows a success message and no generation button is displayed.

---

## 6. Understanding the analysis results

### Current pages
Calculated from existing question page assignments per group.

### Blank pages needed
Number of extra pages required so the group reaches the common target.

### Final pages
Target page count after normalization. In the current logic, this includes cover/page-structure alignment used by PDF generation.

Rows highlighted in red indicate groups that need added blank pages.

---

## 7. Generated ZIP content

The ZIP archive contains subfolders:

- `Questionnaires/`
- `Grilles de réponses/`
- `Formulaires de correction/`

Each folder includes one PDF per group (A, B, C, etc... depending on group numbering).

---

## 8. Notes on page behavior

- Normalization is applied to **question sheets** and **correction forms** by adding blank pages when needed.
- Correction generation uses non-shuffled rendering internally to preserve explicit teacher-defined page breaks.
- Answer sheets are generated using native OfflineQuiz logic.

---

## 9. Error handling and troubleshooting

### “No offline quiz found”
The activity reference is missing or invalid.

### “This exam does not contain any questions”
Add at least one question to the OfflineQuiz groups.

### “Question type is not compatible”
Replace unsupported question types with supported ones.

### “Failed to generate PDF files”
Possible causes:
- temporary file issue,
- PDF generation failure for one or more groups,
- ZIP creation/read failure.

Try again after checking:
- OfflineQuiz configuration,
- group-question assignments,
- server write access to Moodle temp directories.

---

## 10. Best practices

- Finalize group/question layout before generation.
- Keep page assignments clean and intentional in OfflineQuiz editing.
- Test generation once before mass printing.

---

## 11. Version context

Plugin component: `local_offlinequizaddons`  
Release line observed: `1.0.0`