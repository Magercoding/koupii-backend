<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="🎓 Koupii LMS API - Learning Management System",
 *     version="1.0.0",
 *     description="
# 🌟 Welcome to Koupii LMS API

A comprehensive Learning Management System API designed for language learning and test preparation (IELTS, TOEFL, etc.).

## 🚀 Quick Navigation

### 👨‍🎓 **STUDENT PORTAL**
- 📚 View enrolled classes and assignments
- ✍️ Complete writing, reading, listening, and speaking tasks
- 📊 Track progress and view analytics
- 🏆 Check leaderboards and achievements
- 💬 Participate in class discussions

### 👨‍🏫 **TEACHER PORTAL** 
- 🎯 Create and manage classes
- 📝 Assign tasks to students
- ✅ Review and grade submissions
- 📈 Monitor student progress
- 🔧 Configure class settings

### 🔧 **ADMIN PORTAL**
- 👥 Manage all users and classes
- 🛠️ System configuration
- 📊 Platform-wide analytics
- 🔐 User permissions management

## 🔐 Authentication

All endpoints require Bearer token authentication unless specified otherwise.

```bash
Authorization: Bearer YOUR_TOKEN
```

## 📝 API Standards

- **Base URL**: `https://api.koupii.com/api/v1`
- **Content-Type**: `application/json`
- **Date Format**: `ISO 8601` (YYYY-MM-DDTHH:mm:ssZ)
- **UUID Format**: Standard UUID v4

## 🏷️ Response Codes

- `✅ 200-299`: Success
- `❌ 400-499`: Client Error  
- `🔥 500-599`: Server Error

## 🎯 Task Types

- **✍️ Writing**: Essays, reports, descriptions
- **📖 Reading**: Comprehension, analysis 
- **🎧 Listening**: Audio comprehension
- **🗣️ Speaking**: Recorded responses

## 🔄 Assignment Workflow

1. **Teacher** creates and assigns tasks to class
2. **Students** receive notifications and complete assignments
3. **System** auto-grades or queues for teacher review
4. **Results** posted with detailed feedback
5. **Analytics** track progress over time

---

*For support, contact: support@koupii.com*
     ",
     @OA\Contact(
         email="support@koupii.com",
         name="Koupii Support Team",
         url="https://koupii.com/support"
     ),
     @OA\License(
         name="MIT",
         url="https://opensource.org/licenses/MIT"
     )
 )
 * 
 * @OA\Server(
 *     url="https://api-koupii.magercoding.com",
 *     description="Production Server"
 * )
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT Authorization header using the Bearer scheme. Enter 'Bearer' [space] and then your token."
 * )
 *
 * @OA\Tag(
 *     name="👨‍🎓 STUDENT PORTAL",
 *     description="
## 🎓 Student Learning Hub

**Core Features:**
- 📚 Browse enrolled classes
- ✍️ Complete assignments and tasks  
- 📊 Track learning progress
- 🏆 View achievements and rankings
- 💬 Class discussions and help

**Workflow:**
1. Login and view dashboard
2. Check new assignments 
3. Complete tasks with timer
4. Submit and await results
5. Review feedback and improve
     "
 )
 *
 * @OA\Tag(
 *     name="👨‍🏫 TEACHER PORTAL", 
 *     description="
## 👨‍🏫 Teaching & Management Hub

**Core Features:**
- 🎯 Create and manage classes
- 📝 Design custom assignments
- ✅ Review student submissions
- 📈 Monitor class analytics  
- 🔧 Configure learning settings

**Workflow:**
1. Set up classes and invite students
2. Create assignments with rubrics
3. Monitor student progress
4. Grade and provide feedback
5. Analyze performance trends
     "
 )
 *
 * @OA\Tag(
 *     name="🔧 ADMIN PORTAL",
 *     description="
## 🔧 System Administration

**Core Features:**
- 👥 User account management
- 🏢 Institution-wide settings
- 📊 Platform analytics
- 🔐 Security and permissions
- 🛠️ System configuration

**Workflow:**
1. Monitor platform health
2. Manage user accounts
3. Configure system settings
4. Generate usage reports
5. Handle support requests
     "
 )
 *
 * @OA\Tag(
 *     name="🔐 AUTHENTICATION",
 *     description="
## 🔐 User Authentication & Authorization

**Available Methods:**
- 📧 Email/Password login
- 🌐 Social media authentication (Google, Facebook)
- 🔑 JWT token-based sessions
- 🔄 Refresh token management
- 📱 Password reset via email

**Security Features:**
- 🛡️ Rate limiting
- 🔒 Secure password hashing
- 📊 Login attempt monitoring
- 🚫 Account lockout protection
     "
 )
 *
 * @OA\Tag(
 *     name="💡 QUICK START",
 *     description="
## 💡 API Quick Start Guide

**For Students:**
1. `POST /auth/login` - Authenticate
2. `GET /student/dashboard` - View dashboard 
3. `GET /assignments` - Check assignments
4. `POST /assignments/{id}/submit` - Submit work

**For Teachers:**
1. `POST /auth/login` - Authenticate
2. `POST /classes` - Create class
3. `POST /assignments` - Create assignment  
4. `GET /classes/{id}/analytics` - View progress

**For Developers:**
- All responses include consistent JSON structure
- Error handling with detailed messages
- Pagination for list endpoints
- Real-time notifications via WebSocket
     "
 )
 */
class OpenApiSpec
{
    // This class serves as the main documentation entry point
    // Individual endpoint documentation is defined in their respective classes
}



