# CloudEwork API Documentation

## Base URL
```
Development: http://localhost:8000/api
Production: https://api.cloudework.com
```

## Authentication
All authenticated endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Response Format
All responses follow this structure:
```json
{
  "success": true,
  "data": {},
  "message": "Success message",
  "errors": []
}
```

---

## 🔐 Authentication Endpoints

### Register Coach
```http
POST /api/register/coach
```

**Request Body:**
```json
{
  "email": "coach@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "first_name": "Juan",
  "last_name": "Pérez",
  "phone": "+506-8888-8888"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "coach@example.com",
      "first_name": "Juan",
      "last_name": "Pérez",
      "role": "coach"
    },
    "token": "1|abcd1234..."
  }
}
```

### Login
```http
POST /api/login
```

**Request Body:**
```json
{
  "email": "coach@example.com",
  "password": "SecurePass123!"
}
```

### Logout
```http
POST /api/logout
```

---

## 👥 Athletes Endpoints

### List Athletes (Coach)
```http
GET /api/athletes
```

**Query Parameters:**
- `status`: active, inactive, on_hold
- `search`: Search by name or email
- `per_page`: Results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "athletes": [
      {
        "id": 1,
        "user": {
          "first_name": "María",
          "last_name": "González",
          "email": "maria@example.com",
          "avatar_url": null
        },
        "status": "active",
        "start_date": "2024-01-15",
        "total_workouts": 45,
        "total_prs": 8,
        "last_workout_date": "2024-01-19"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "total": 24,
      "per_page": 15
    }
  }
}
```

### Create Athlete
```http
POST /api/athletes
```

**Request Body:**
```json
{
  "email": "athlete@example.com",
  "password": "SecurePass123!",
  "first_name": "María",
  "last_name": "González",
  "phone": "+506-7777-7777",
  "date_of_birth": "1995-05-20",
  "gender": "female",
  "height_cm": 165.5,
  "weight_kg": 62.0,
  "goals": "Mejorar fuerza y resistencia",
  "emergency_contact_name": "Pedro González",
  "emergency_contact_phone": "+506-6666-6666"
}
```

### Get Athlete Details
```http
GET /api/athletes/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "athlete": {
      "id": 1,
      "user": {...},
      "date_of_birth": "1995-05-20",
      "gender": "female",
      "height_cm": 165.5,
      "weight_kg": 62.0,
      "goals": "Mejorar fuerza y resistencia",
      "status": "active",
      "start_date": "2024-01-15",
      "statistics": {
        "total_workouts": 45,
        "total_prs": 8,
        "current_streak": 5,
        "completion_rate": 92
      },
      "recent_prs": [...]
    }
  }
}
```

### Update Athlete
```http
PUT /api/athletes/{id}
```

### Delete Athlete
```http
DELETE /api/athletes/{id}
```

---

## 🏋️ Workouts Endpoints

### List Workouts
```http
GET /api/workouts
```

**Query Parameters:**
- `type`: benchmark, custom, strength, metcon, skill, mixed
- `is_benchmark`: true, false
- `difficulty`: beginner, intermediate, advanced, rx
- `search`: Search by name or description
- `tags`: Filter by tags (comma-separated)
- `per_page`: Results per page

**Response:**
```json
{
  "success": true,
  "data": {
    "workouts": [
      {
        "id": 1,
        "name": "Fran",
        "slug": "fran",
        "description": "21-15-9 reps for time",
        "workout_type": "benchmark",
        "benchmark_category": "girl",
        "difficulty_level": "rx",
        "is_benchmark": true,
        "estimated_duration_minutes": 10,
        "workout_structure": {
          "format": "for_time",
          "rounds": 3,
          "rep_scheme": [21, 15, 9],
          "movements": [
            {
              "name": "Thrusters",
              "weight": {"rx": 95, "scaled": 65},
              "unit": "lbs"
            },
            {
              "name": "Pull-ups",
              "modification": "kipping or strict"
            }
          ]
        },
        "tags": ["benchmark", "girl", "gymnastics", "weightlifting"],
        "times_assigned": 142
      }
    ]
  }
}
```

### Get Workout Details
```http
GET /api/workouts/{id}
```

### Create Workout
```http
POST /api/workouts
```

**Request Body:**
```json
{
  "name": "Custom WOD 1",
  "description": "High intensity metcon",
  "workout_type": "metcon",
  "difficulty_level": "intermediate",
  "estimated_duration_minutes": 20,
  "workout_structure": {
    "format": "amrap",
    "time_cap": 20,
    "movements": [
      {
        "name": "Box Jumps",
        "reps": 15,
        "height": {"rx": 24, "scaled": 20},
        "unit": "inches"
      },
      {
        "name": "Kettlebell Swings",
        "reps": 20,
        "weight": {"rx": 53, "scaled": 35},
        "unit": "lbs"
      },
      {
        "name": "Burpees",
        "reps": 10
      }
    ]
  },
  "scaling_options": {
    "beginner": "Reduce reps by 50%",
    "intermediate": "As prescribed",
    "advanced": "Add weight vest"
  },
  "equipment_needed": ["box", "kettlebell"],
  "tags": ["metcon", "conditioning", "bodyweight"],
  "notes": "Focus on consistent pacing"
}
```

### Update Workout
```http
PUT /api/workouts/{id}
```

### Delete Workout
```http
DELETE /api/workouts/{id}
```

---

## 📅 Workout Assignments Endpoints

### List Assignments
```http
GET /api/assignments
```

**Query Parameters:**
- `athlete_id`: Filter by athlete
- `date_from`: Start date (YYYY-MM-DD)
- `date_to`: End date (YYYY-MM-DD)
- `is_completed`: true, false
- `per_page`: Results per page

### Create Assignment
```http
POST /api/assignments
```

**Request Body (Individual):**
```json
{
  "workout_id": 1,
  "athlete_id": 5,
  "scheduled_date": "2024-01-20",
  "notes": "Focus on form, take breaks as needed",
  "priority": "high"
}
```

**Request Body (Group):**
```json
{
  "workout_id": 1,
  "group_id": 2,
  "scheduled_date": "2024-01-20",
  "notes": "Competition prep workout",
  "priority": "high"
}
```

### Bulk Assign Workouts
```http
POST /api/assignments/bulk
```

**Request Body:**
```json
{
  "workout_id": 1,
  "athlete_ids": [1, 2, 3, 4, 5],
  "scheduled_date": "2024-01-20",
  "notes": "Team workout day"
}
```

### Update Assignment
```http
PUT /api/assignments/{id}
```

### Delete Assignment
```http
DELETE /api/assignments/{id}
```

---

## 📊 Workout Results Endpoints

### Submit Result
```http
POST /api/results
```

**Request Body (For Time):**
```json
{
  "assignment_id": 15,
  "completed_at": "2024-01-19T10:30:00Z",
  "time_seconds": 263,
  "rx_or_scaled": "rx",
  "feeling_rating": 4,
  "notes": "Felt strong, PR!",
  "result_data": {
    "time": "4:23",
    "movements": [
      {"name": "Thrusters", "weight": 95, "completed": true},
      {"name": "Pull-ups", "reps": 63, "completed": true}
    ]
  }
}
```

**Request Body (AMRAP):**
```json
{
  "assignment_id": 16,
  "completed_at": "2024-01-19T14:00:00Z",
  "rounds_completed": 12,
  "reps_completed": 8,
  "rx_or_scaled": "scaled",
  "feeling_rating": 3,
  "notes": "Tough one today",
  "result_data": {
    "rounds": 12,
    "partial_reps": 8,
    "total_reps": 368
  }
}
```

### List Results
```http
GET /api/results
```

**Query Parameters:**
- `athlete_id`: Filter by athlete
- `workout_id`: Filter by workout
- `date_from`: Start date
- `date_to`: End date
- `is_pr`: true, false

### Get Result Details
```http
GET /api/results/{id}
```

### Update Result
```http
PUT /api/results/{id}
```

### Delete Result
```http
DELETE /api/results/{id}
```

---

## 🏆 Personal Records Endpoints

### List PRs
```http
GET /api/prs
```

**Query Parameters:**
- `athlete_id`: Filter by athlete
- `movement_name`: Filter by movement
- `record_type`: weight, time, reps, distance

**Response:**
```json
{
  "success": true,
  "data": {
    "prs": [
      {
        "id": 1,
        "movement_name": "Back Squat",
        "record_type": "weight",
        "value": 315,
        "unit": "lbs",
        "achieved_at": "2024-01-15T10:00:00Z",
        "notes": "New PR!",
        "previous_pr": 295
      },
      {
        "id": 2,
        "movement_name": "Fran",
        "record_type": "time",
        "value": 263,
        "unit": "seconds",
        "achieved_at": "2024-01-19T10:30:00Z"
      }
    ]
  }
}
```

### Create PR
```http
POST /api/prs
```

---

## 👥 Groups Endpoints

### List Groups
```http
GET /api/groups
```

### Create Group
```http
POST /api/groups
```

**Request Body:**
```json
{
  "name": "Morning Class",
  "description": "6 AM regulars",
  "color": "#FF6B35"
}
```

### Add Members to Group
```http
POST /api/groups/{id}/members
```

**Request Body:**
```json
{
  "athlete_ids": [1, 2, 3, 4, 5]
}
```

### Remove Member from Group
```http
DELETE /api/groups/{id}/members/{athlete_id}
```

---

## 💬 Messaging Endpoints

### List Conversations
```http
GET /api/conversations
```

### Get Conversation
```http
GET /api/conversations/{id}
```

### Send Message
```http
POST /api/messages
```

**Request Body:**
```json
{
  "conversation_id": 1,
  "message": "Great work on today's workout!"
}
```

### Mark as Read
```http
PUT /api/messages/{id}/read
```

---

## 📈 Analytics Endpoints

### Coach Dashboard Stats
```http
GET /api/analytics/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_athletes": 24,
    "active_athletes": 22,
    "workouts_this_week": 87,
    "completion_rate": 92,
    "prs_this_month": 43,
    "trends": {
      "athletes_change": 12,
      "workouts_change": 18,
      "completion_change": 5,
      "prs_change": 23
    }
  }
}
```

### Athlete Progress
```http
GET /api/analytics/athlete/{id}/progress
```

**Query Parameters:**
- `period`: week, month, quarter, year

---

## 🔔 Notifications Endpoints

### List Notifications
```http
GET /api/notifications
```

### Mark as Read
```http
PUT /api/notifications/{id}/read
```

### Mark All as Read
```http
PUT /api/notifications/read-all
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 200  | Success |
| 201  | Created |
| 400  | Bad Request |
| 401  | Unauthorized |
| 403  | Forbidden |
| 404  | Not Found |
| 422  | Validation Error |
| 429  | Too Many Requests |
| 500  | Server Error |

## Rate Limiting
- 60 requests per minute for authenticated users
- 30 requests per minute for unauthenticated users
