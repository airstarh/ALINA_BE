# Pinned Comment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add one pinned first-level comment per root tale without changing the existing paginated comment response contract.

**Architecture:** A dedicated backend action validates and atomically changes the pin inside one root tale. Comment feeds keep ordinary comments in `data.tale` and existing pagination in `meta.tale`, while adding the single full pinned comment as `meta.pinned_comment`. The Vue comment component renders that object before its ordinary paginated list and refreshes only the local comment list after a pin change.

**Tech Stack:** PHP 8.2, Illuminate Database, Vue 3.

**Spec:** Approved directly in the conversation on 2026-10-23.

## Global Constraints

- Never create a Git commit unless explicitly requested.
- Never run the JavaScript SPA build without asking immediately beforehand.
- Preserve every existing AJAX response field and the meanings of `data.tale` and `meta.tale`.
- A root tale has zero or one pinned first-level comment; pagination applies only to ordinary comments.

## Review Focus

- A nested comment must be rejected as a pin target.
- Authorization must use the root tale owner, not the comment owner.
- Ordinary comment editing must not bypass the dedicated pin action.
- Pin replacement must leave exactly one pinned sibling after concurrent requests serialize.
- Fetching the pinned object must not replace ordinary `meta.tale` pagination.

---

### Task 1: Backend pinning rules and response contract

**Files:**
- Modify: `server/srv/alina/mvc/Controller/Tale.php`
- Test: `server/srv/alina/tests/TalePinnedCommentRulesTest.php`

**Interfaces:**
- Produces: `POST /tale/StickComment/{commentId}` accepting `is_sticked`.
- Produces: comment-feed `meta.pinned_comment`, containing a full comment object or `null`.
- Preserves: ordinary comments in `data.tale` and their pagination in `meta.tale`.

- [x] Write and run a failing rule test for valid first-level and invalid nested/non-comment targets.
- [x] Implement validation, root-owner authorization, atomic replacement, and upsert bypass prevention.
- [x] Exclude the pin from ordinary pagination and add it to metadata.
- [x] Run the focused test and PHP syntax checks.

### Task 2: Frontend rendering and local refresh

**Files:**
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/elements/form/Comment.vue`

**Interfaces:**
- Consumes: `meta.pinned_comment` and `POST /tale/StickComment/{commentId}`.
- Preserves: existing ordinary paginator behavior.

- [x] Render the pinned comment once before `feed` without inserting it into `feed`.
- [x] Show the pin checkbox only on first-level comments to Admin, Moderator, or root-tale owner.
- [x] Refresh only the expanded local comment list after pin/unpin.
- [x] Run non-build static checks and inspect the exact diff.

