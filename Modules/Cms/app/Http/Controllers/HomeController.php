<?php

namespace Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use TomatoPHP\FilamentCms\Models\Post;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('cms::index');
    }

    public function about()
    {
        $page = load_page('/about');
        $page->views += 1;
        $page->save();
        return view('cms::about', [
            'page' => $page
        ]);
    }

    public function donate()
    {
        $page = load_page('/donate');
        $page->views += 1;
        $page->save();
        return view('cms::donate', [
            'page' => $page
        ]);
    }

    private function applyFilter(Builder $query,string $key='category'): Builder
    {
        if(request()->has('search') && !empty('search')){
            $query
                ->where('slug', 'like', '%'.request()->search.'%');
        }

        if(request()->has('sort') && !empty('sort')){
            if(request()->get('sort') === 'popular'){
                $query->orderBy('views', 'desc');
            }
            elseif (request()->get('sort') === 'recent'){
                $query->orderBy('created_at', 'desc');
            }
            elseif (request()->get('sort') === 'alphabetical'){
                $query->orderBy('title');
            }
            else {
                $query->orderBy('created_at', 'desc');
            }
        }

        if(request()->has($key) && !empty($key)){
            $query->whereHas('categories', function ($query) use ($key){
                $query->where('slug', request()->get($key));
            });
        }

        return $query;
    }

    private function applyAccountFilter(Builder $query): Builder
    {
        if(request()->has('search') && !empty('search')){
            $query
                ->where('username', 'like', '%'.request()->search.'%');
        }

        if(request()->has('sort') && !empty('sort')){
            if(request()->get('sort') === 'popular'){
                //Order By Type where type is 'verified', 'public'
                $query->orderByRaw("case when type = 'verified' then 1 when type = 'public' then 2 else 3 end");
            }
            elseif (request()->get('sort') === 'recent'){
                $query->orderBy('created_at', 'desc');
            }
            elseif (request()->get('sort') === 'alphabetical'){
                $query->orderBy('name');
            }
            else {
                $query->inRandomOrder();
            }
        }

        return $query;
    }


    public function openSource(Request $request)
    {
        $openSources = Post::query()
            ->where('type', 'open-source')
            ->where('is_published', 1);

        $openSources = $this->applyFilter($openSources);

        $openSources = $openSources->paginate(10);
        return view('cms::open-source', [
            'openSources' => $openSources
        ]);
    }

    public function docs($docs)
    {
        $openSources = Post::query()
            ->where('type', 'open-source')
            ->where('is_published', 1);

        $openSources = $this->applyFilter($openSources);

        $openSources = $openSources->orderBy('slug')->get();

        $docs = Post::query()
            ->where('slug', $docs)
            ->first();

        if($docs){
            $docs->views += 1;
            $docs->save();

            return view('cms::docs', [
                "docs" => $docs,
                "openSources" => $openSources
            ]);
        }
        else {
            abort(404);
        }

    }

    public function portfolios()
    {
        $portfolios = Post::query()
            ->where('type', 'portfolio')
            ->where('is_published', 1);

        $portfolios = $this->applyFilter($portfolios);

        $portfolios = $portfolios->paginate(12);
        return view('cms::portfolios', [
            'portfolios' => $portfolios
        ]);
    }

    public function portfolio($portfolio)
    {
        $portfolio = Post::query()->where('slug', $portfolio)->first();
        $portfolio->views += 1;
        $portfolio->save();
        return view('cms::portfolio', [
            "portfolio" => $portfolio
        ]);
    }


    public function services()
    {
        $services = Post::query()
            ->where('type', 'service')
            ->where('is_published', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return view('cms::services', [
            'services' => $services
        ]);
    }

    public function service($service)
    {
        $service = Post::query()->where('slug', $service)->first();
        $service->views += 1;
        $service->save();
        return view('cms::service', [
            "service" => $service
        ]);
    }

    public function blog(Request $request)
    {
        $posts = Post::query()
            ->where('type', 'post')
            ->where('is_published', 1);

        $posts = $this->applyFilter($posts);

        $posts = $posts->paginate(12);

        return view('cms::blog', [
            "posts" => $posts
        ]);
    }

    public function post($post)
    {
        $post = Post::query()->where('slug', $post)->first();

        if($post){
            $post->views += 1;
            $post->save();

            return view('cms::post', [
                'post' => $post
            ]);
        }
        else {
            abort(404);
        }

    }

    public function contact()
    {
        return view('cms::contact');
    }

    public function issues()
    {
        return view('cms::issues');
    }

    public function page($page)
    {
        $page = Post::query()->where('type', 'page')->where('slug', $page)->first();

        if($page){
            $page->views += 1;
            $page->save();

            return view('cms::page', [
                'page' => $page
            ]);
        }
        else {
            abort(404);
        }
    }

    public function community()
    {
        $accounts = Account::query()->whereHas('accountsMetas', function (Builder $q){
            $q->where('key', 'is_public')->where('value', "1");
        });

        $accounts = $this->applyAccountFilter($accounts);

        $accounts = $accounts->paginate(12);

        return view('cms::community', [
            "accounts" => $accounts
        ]);
    }
}
