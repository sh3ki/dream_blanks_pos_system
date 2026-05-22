<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\FileHelper;

class UploadController extends Controller
{
    public function paymentPhoto(Request $request): Response
    {
        if (empty($_FILES['payment_photo']) || $_FILES['payment_photo']['error'] !== UPLOAD_ERR_OK) {
            return $this->error('No file uploaded', 400);
        }
        $path = FileHelper::upload($_FILES['payment_photo'], 'payments');
        return $this->success(['path' => $path], 'Uploaded');
    }
}
