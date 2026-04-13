<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    use Helper;

    public function fileUpload(Request $request)
    {
        $only_url = request()->input('only_url');
        if ($request->file) {
            $validator = Validator::make($request->all(), [
                'file' => 'required|max:2048|mimes:jpg,jpeg,gif,png,pdf,doc,xlsx,xls',
            ]);
            if ($validator->fails()) {
                return returnData(3000, null, 'File validation failed, Max file size 4MB and supported file type (jpg,jpeg,gif,pdf)');
            }
            $path = $this->insertFile('file');

            if ($only_url){
                return returnData(2000, $path['path']);
            }

            return returnData(2000, $path);
        }
    }

    public function imageUpload(Request $request){
        $path = $this->insertFile('file');
        $imagePath = env('UPLOAD_PATH')."/".$path;

        return response()->json(['location'=>$imagePath]);
    }
}
