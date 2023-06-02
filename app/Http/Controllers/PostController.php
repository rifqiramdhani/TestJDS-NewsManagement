<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\PostDetailResource;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $query = Post::with('user');

        if($request->keyword){
            $query->where('title', 'LIKE', '%' . $request->keyword . '%');
        }

        if($request->orderBy){
            $query->orderBy('id', $request->orderBy);
        }else{
            $query->orderBy('id', 'ASC');
        }

        if($request->pagination){
            if($request->limit){
                $post = $query->paginate($request->limit);
            }else{
                $post = $query->paginate(10);
            }
        }else{
            $post = $query->get();
        }
        
        return response()->json($post);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);

        if($request->file){
            /* proses upload file */
            $fileName  = $this->generateRandomString();
            $extension = $request->file->extension();

            $path = Storage::putFileAs('image', $request->file, $fileName . '.' . $extension);
            $request['image'] = $fileName . '.' . $extension;
        }


        $request['user_id'] = Auth::user()->id;

        $post = Post::create($request->all());

        return new PostDetailResource($post->loadMissing('user:id,email'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $result = Post::with(['user:id,email','comments:id,post_id,user_id,content,created_at'])->findOrFail($id);
        
        return new PostDetailResource($result); 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    { 
        //
        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);

        $post = Post::findOrFail($id)->update($request->all());

        return new PostDetailResource($post->loadMissing('user:id,email'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $post = Post::findOrFail($id);
        $post->delete();

        return new PostDetailResource($post->loadMissing('user:id,email'));
    }

    public function generateRandomString($length = 30) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
