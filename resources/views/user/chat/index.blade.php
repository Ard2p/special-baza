@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">
            <div
                 class="  @guest col-md-9 col-md-push-3 @endguest col-sm-12 ">
                @seoTop
                @contactTop
              <div>
                  <global-chat chat-id="{{$chat->id}}"></global-chat>
              </div>
                @seoBottom
                @contactBottom
            </div>

            @guest
                <div class="col-md-3    col-md-pull-9">
                    <section class="post-header">
                        <div class="auth" style="width: 100%;">
                            <form action="post" id="auth_form">
                                @csrf
                                <h2 class="title">Вход</h2>
                                <div class="form-item image-item">
                                    <label for="">
                                        Ваш email
                                        <input type="email" name="email" placeholder="Введите почту">
                                        <span class="image email"></span>
                                    </label>
                                    <span class="error"></span>
                                </div>
                                <div class="form-item image-item">
                                    <label for="">
                                        Ваш пароль
                                        <input type="password" name="password" placeholder="Введите пароль">
                                        <span class="image lock"></span>
                                    </label>
                                </div>
                                <div class="button">
                                    <button type="submit" class="btn-custom">войти</button>
                                </div>
                            </form>
                            <hr>
                            <a href="/password/reset" class="link-register"><span class="red">Забыли пароль?</span></a>
                            <div class="button">
                                <a href="/register" class="btn-custom black">Регистрация</a>
                            </div>
                        </div>

                    </section>
                    @include('includes.youtube')
                </div>
            @endguest
        </div>



    </div>
    {!! \App\Marketing\ShareList::renderShare() !!}
    @push('after-scripts')
        <script>

            $('[data-toggle="_datepicker"]').datetimepicker({
                format: 'Y/m/d',
                dayOfWeekStart: 1,
                timepicker: false
            });

        </script>
        <style>
            .thumbnail {
                margin-bottom: 0px;
            }
        </style>
    @endpush
@endsection