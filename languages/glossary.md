# Disciple.Tools Training Plugin - Glossary

A reference guide for translators and developers explaining key terms used in this plugin.

---

## Core Concepts

### Training
The primary record type in this plugin. A training is an event or series of events that can be tracked, scheduled, and managed. Trainings can have participants, leaders, and relationships to other trainings.

### Post Type
The WordPress content type used to store training records (`trainings`). Manages how trainings are stored in the database and displayed in the interface.

---

## Training Statuses

### New
A training newly added to the system. Initial state before any action is taken.

### Proposed
Training has been proposed and is in initial conversations. Not yet confirmed.

### Scheduled
Training is confirmed and on the calendar. Ready to occur.

### In Progress
Training is currently active or underway.

### Complete
Training has successfully finished.

### Paused
Training is on hold with potential for future scheduling.

### Closed
Training will no longer happen. Final state for cancelled trainings.

---

## Relationships

### Parent Training
A training that launched or originated another training. The source training in a generational relationship.

### Child Training
A training that was birthed from or spawned by another training. Represents multiplication.

### Peer Training
A related training that is not in a parent/child relationship. Used for trainings that collaborate, are about to merge, or recently split.

### Members
Contacts who are participants or attendees of a training. Distinct from leaders.

### Leaders
Contacts who have leadership roles in a training. Responsible for facilitating or directing the training.

### Training Coach / Church Planter
The person who planted and/or is coaching the training. Provides oversight and guidance.

### Assigned To
The main person responsible for reporting on a training. This person automatically receives access to the training record.

---

## Location Terms

### Locations
Geographic areas where trainings occur. Uses a location grid system for general area assignment.

### Location Grid
The general geographic area where a training is located. Used for mapping and filtering.

### Contact Address
Physical address of the training location. Can be displayed on maps.

---

## Fields & Data

### Meeting Times
Scheduled times when trainings occur. Supports multiple datetime entries for recurring events.

### Tags
Labels used to group related trainings. Helpful for filtering and organizing records.

### Tasks
Action items or follow-ups associated with a training.

### Video Link
URL to a video chat service or meeting platform. Used for remote training delivery.

### Notes / Training Notes
Free-form text for capturing details about training schedules and additional context.

### Requires Update
A flag indicating whether a training needs attention or follow-up from the assigned person.

---

## User Roles

### Training Admin
A user with administrative access to all trainings. Can view and update any training record.

### Multiplier
A user engaged in multiplication activities. Typically associated with training leadership.

---

## Connections to Other Records

### People Groups
Specific populations or ethnic groups served by a training.

### Groups
Disciple.Tools groups associated with or emerging from a training.

### Contacts
Individual people who participate in or lead trainings.

---

## Metrics & Visualization

### Training Maps
Map visualization showing trainings by geographic location.

### Training Tree / Generation Tree
Hierarchical visualization of parent-child training relationships. Shows how trainings multiply over generations.

### Multiplying Only
A filter showing only trainings that have produced child trainings. Used to analyze multiplication growth.

### Member Count
The number of participants in a training. Can be automatically calculated or manually set.

### Leader Count
The number of leaders in a training.

---

## Interface Elements

### Tiles
Modular display sections organizing training information:
- **Status Tile**: Shows assigned user, coaches, and current status
- **Details Tile**: Video link, notes, locations, address, people groups
- **Relationships Tile**: Members, leaders, and connections
- **Meeting Times Tile**: Scheduled occurrence times
- **Other Tile**: Parent/peer/child trainings, groups, tags

---

## Technical Terms

### Module
A component of functionality that can be enabled or disabled. The trainings module (`trainings_base`) provides core training features.

### Activity Log
Records of training creation, updates, and status changes. Used for tracking history and reporting.

### Migration
Database schema updates that run automatically when the plugin is updated.

### REST API
Programming interface for accessing training data. Used by maps and tree visualizations.

---

## Activity Events

### training_new
Event logged when a new training is created.

### training_in_progress
Event logged when a training status changes to "In Progress".

### training_completed
Event logged when a training is marked as complete.
