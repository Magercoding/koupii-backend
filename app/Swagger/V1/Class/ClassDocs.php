<?php

namespace App\Swagger\V1\Class;

use OpenApi\Annotations as OA;

class ClassDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/classes",
     *     tags={"Classes"},
     *     summary="Get all classes",
     *     description="Admin & Student can see all classes, Teacher can only see their own classes.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of classes",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22"),
     *                 @OA\Property(property="name", type="string", example="Biology Class"),
     *                 @OA\Property(property="description", type="string", example="This is class biology"),
     *                 @OA\Property(property="cover_image", type="string", nullable=true, example=null),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-25T08:44:37.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-29T07:35:08.000000Z"),
     *                 @OA\Property(
     *                     property="teacher",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="240726c6-64b3-4cbb-ae47-923dc2b46d37"),
     *                     @OA\Property(property="name", type="string", example="Fika Teacher"),
     *                     @OA\Property(property="email", type="string", example="teacher1@example.com"),
     *                     @OA\Property(property="bio", type="string", example="Lorem ipsum dolor sit amet"),
     *                     @OA\Property(property="avatar", type="string", nullable=true, example="http://localhost:8000/storage/avatar/68d3bb777d57b.png")
     *                 ),
     *                 @OA\Property(
     *                     property="students",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid", example="e76ed3e1-f9ea-45b4-84a3-275fa0c24715"),
     *                         @OA\Property(property="name", type="string", example="Fita"),
     *                         @OA\Property(property="avatar", type="string", nullable=true, example="http://localhost:8000/storage/avatar/68d50da003002.png")
     *                     )
     *                 ),
     *                 @OA\Property(property="class_code", type="string", example="1234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function getAllClasses() {}

    /**
     * @OA\Post(
     *     path="/api/v1/classes/create",
     *     tags={"Classes"},
     *     summary="Create a new class",
     *     description="Only admin and teacher can create a new class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Theory Class"),
     *                 @OA\Property(property="description", type="string", example="This is theory class"),
     *                 @OA\Property(property="class_code", type="string", example="ABC12345"),
     *                 @OA\Property(property="cover_image", type="string", format="binary", description="Upload class cover image (jpg, png)"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Class created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Class created successfully")
     *         )
     *     ),
     *     @OA\Response(response=409, description="Class name or code already exists"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createClass() {}

    /**
     * @OA\Get(
     *     path="/api/v1/classes/{id}",
     *     tags={"Classes"},
     *     summary="Get class details",
     *     description="Get class detail including teacher info.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Class details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22"),
     *             @OA\Property(property="name", type="string", example="Biology Class"),
     *             @OA\Property(property="description", type="string", example="This is class biology"),
     *             @OA\Property(property="cover_image", type="string", nullable=true, example=null),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-25T08:44:37.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-29T07:35:08.000000Z"),
     *             @OA\Property(
     *                 property="teacher",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="240726c6-64b3-4cbb-ae47-923dc2b46d37"),
     *                 @OA\Property(property="name", type="string", example="Fika Teacher"),
     *                 @OA\Property(property="email", type="string", example="teacher1@example.com"),
     *                 @OA\Property(property="bio", type="string", example="Lorem ipsum dolor sit amet"),
     *                 @OA\Property(property="avatar", type="string", nullable=true, example="http://localhost:8000/storage/avatar/68d3bb777d57b.png")
     *             ),
     *             @OA\Property(
     *                 property="students",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid", example="e76ed3e1-f9ea-45b4-84a3-275fa0c24715"),
     *                     @OA\Property(property="name", type="string", example="Fita"),
     *                     @OA\Property(property="avatar", type="string", nullable=true, example="http://localhost:8000/storage/avatar/68d50da003002.png")
     *                 )
     *             ),
     *             @OA\Property(property="class_code", type="string", example="1234567")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Class not found")
     * )
     */
    public function getClassDetail() {}

    /**
     * @OA\Post(
     *     path="/api/v1/classes/update/{id}",
     *     tags={"Classes"},
     *     summary="Update class",
     *     description="Only admin and teacher (class owner) can update class data.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="_method",
     *         in="query",
     *         required=true,
     *         description="Override HTTP method for PATCH requests",
     *         @OA\Schema(type="string", example="PATCH")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Biology Class"),
     *                 @OA\Property(property="description", type="string", example="This is biology class"),
     *                 @OA\Property(property="class_code", type="string", example="qwertyui"),
     *                 @OA\Property(property="cover_image", type="string", format="binary", description="Upload class cover image (jpg, png)"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Class updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Class updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Class not found"),
     *     @OA\Response(response=409, description="Class name or code already exists"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateClass() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/classes/delete/{id}",
     *     tags={"Classes"},
     *     summary="Delete a class",
     *     description="Only admin and teacher (class owner) can delete a class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Class deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Class deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Class not found")
     * )
     */
    public function deleteClass() {}
}

