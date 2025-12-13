<?php

namespace App\Swagger\V1\Listening;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Koupii Listening Module API",
 *     description="Comprehensive API documentation for listening module with 15 question types, audio processing, analytics, and student assessment capabilities",
 *     @OA\Contact(
 *         email="developer@koupii.com",
 *         name="Koupii Development Team"
 *     ),
 *     @OA\License(
 *         name="MIT License",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 */

/**
 * @OA\Server(
 *     url="/api/v1",
 *     description="Koupii API V1 Server"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT Bearer token"
 * )
 */

/**
 * === LISTENING MODULE OVERVIEW ===
 * 
 * The Listening Module provides comprehensive functionality for creating, managing, 
 * and analyzing listening comprehension tasks with the following features:
 * 
 * 🎯 **15 Question Types Supported**:
 * - QT1: Multiple Choice (Single Answer)
 * - QT2: Multiple Choice (Multiple Answers)  
 * - QT3: True/False/Not Given
 * - QT4: Fill in the Blanks
 * - QT5: Dropdown Selection
 * - QT6: Matching (Words/Phrases)
 * - QT7: Sequencing/Ordering
 * - QT8: Short Answer
 * - QT9: Note Completion
 * - QT10: Summary Completion
 * - QT11: Sentence Completion
 * - QT12: Classification
 * - QT13: Plan/Map/Diagram Labeling
 * - QT14: Table Completion
 * - QT15: Flow Chart Completion
 * 
 * 🎵 **Audio Features**:
 * - Upload & processing (MP3, WAV, M4A, AAC, OGG)
 * - Automatic transcript generation
 * - Audio segmentation with timestamps
 * - Waveform visualization
 * - Replay controls and interaction logging
 * - Multi-language support
 * 
 * 📊 **Analytics & Assessment**:
 * - Real-time performance tracking
 * - Question type analysis across all 15 types
 * - Audio interaction analytics (plays, pauses, segments)
 * - Student progress monitoring
 * - Comparative performance analysis
 * - Automated scoring with manual override
 * - Comprehensive reporting (JSON, PDF, Excel)
 * 
 * 🏗️ **Architecture**:
 * - Clean controller separation (Tasks, Questions, Audio, Submissions, Analytics)
 * - RESTful API design with proper HTTP methods
 * - Comprehensive validation for each question type
 * - Bulk operations support
 * - Auto-save functionality
 * - Export capabilities
 * 
 * 🔐 **Security & Permissions**:
 * - JWT Bearer authentication required
 * - Role-based access control
 * - Input validation and sanitization
 * - Rate limiting protection
 * - Secure file upload handling
 * 
 * === API STRUCTURE ===
 * 
 * **Base URL**: `/api/v1/listening/`
 * 
 * **Main Endpoints**:
 * - `/tasks/*` - Listening task management
 * - `/questions/*` - Question creation and management (15 types)
 * - `/audio/*` - Audio file processing and interaction
 * - `/submissions/*` - Student submissions and grading
 * - `/answers/*` - Answer management and validation
 * - `/analytics/*` - Performance analytics and reporting
 * 
 * **Response Format**:
 * All responses follow a consistent structure:
 * ```json
 * {
 *   "status": "success|error",
 *   "message": "Human readable message",
 *   "data": {}, // Response data
 *   "meta": {}  // Pagination/additional metadata (optional)
 * }
 * ```
 * 
 * **Error Handling**:
 * - 200: Success
 * - 201: Created successfully  
 * - 400: Bad request
 * - 401: Unauthorized
 * - 403: Forbidden
 * - 404: Not found
 * - 422: Validation error
 * - 500: Internal server error
 * 
 * **Question Type Validation**:
 * Each of the 15 question types has specific validation rules:
 * - Structure validation (required fields, data types)
 * - Content validation (answer format, options structure)
 * - Audio segment compatibility
 * - Scoring configuration
 * 
 * **Audio Processing Pipeline**:
 * 1. File upload with validation
 * 2. Format conversion and optimization
 * 3. Metadata extraction (duration, quality, etc.)
 * 4. Transcript generation (automatic)
 * 5. Segment creation (manual or automatic)
 * 6. Waveform data generation
 * 
 * **Analytics Capabilities**:
 * - Individual student performance tracking
 * - Task-level analytics (completion rates, average scores)
 * - Question type analysis across all 15 types
 * - Audio interaction patterns
 * - Comparative student analysis
 * - Progress trends and improvement tracking
 * - Custom report generation
 * 
 * === GETTING STARTED ===
 * 
 * 1. **Authentication**: Obtain JWT token via auth endpoints
 * 2. **Create Task**: POST /listening/tasks with basic info
 * 3. **Upload Audio**: POST /listening/audio/upload
 * 4. **Process Audio**: POST /listening/audio/process (transcript + segments)
 * 5. **Add Questions**: POST /listening/questions (any of 15 types)
 * 6. **Publish Task**: PATCH /listening/tasks/{id}/publish
 * 7. **Students Submit**: POST /listening/submissions
 * 8. **View Analytics**: GET /listening/analytics/*
 * 
 * For detailed examples and complete API reference, explore the endpoints below.
 */

// Include all schema definitions
require_once 'ListeningSchemas.php';

// Include all endpoint documentations
require_once 'ListeningTaskSwagger.php';
require_once 'ListeningQuestionSwagger.php';
require_once 'ListeningAudioSwagger.php';
require_once 'ListeningSubmissionSwagger.php';
require_once 'ListeningAnalyticsSwagger.php';