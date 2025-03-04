<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Image & Video Upload</title>
</head>

<body>

    <h2>Upload Images & Videos</h2>
    <form action="{{ route('TestMediaUpload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <!-- Include CSRF Token -->
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <!-- Deal ID (for updating an existing record) -->
        <label for="id">Deal ID (Optional for Update):</label>
        <input type="text" name="id" id="id" placeholder="Enter Deal ID"><br><br>

        <!-- Image Upload -->
        <label for="images">Select Images:</label>
        <input type="file" name="images[]" id="images" multiple><br><br>

        <!-- Video Upload -->
        <label for="videos">Select Videos:</label>
        <input type="file" name="videos[]" id="videos" multiple><br><br>

        <!-- Submit Button -->
        <button type="submit">Upload</button>
    </form>

</body>

</html>
