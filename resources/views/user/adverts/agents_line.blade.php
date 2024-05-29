@if(Auth::check() && Auth::user()->id !== $advert->user_id)
    @if(isset($link) || $advert->isAgent())
        <div class="col-sm-6 col-md-6 side-content">
            <h4>Расстояние до автора объявления: {{$advert->getAgentsCount() -1}} агентов</h4>
          {{--  <div class="bs-vertical-wizard">
                <ul>
                    @php
                    $current_id = !$advert->isAgent() ? $link->advert->pivot->user_id : Auth::id();
                    @endphp
                    @foreach($advert->buildAgents($current_id) as $agent)
                        <li class="{{$loop->first ? 'complete' : 'prev-step'}}">
                            <a href="#">#{{$agent->id}} {!!  $loop->first ? '<i class="ico fa fa-check ico-green"></i>' : ''!!}
                                <span class="desc">{{$agent->id === Auth::id() ? 'Это Вы' : ''}}</span>
                            </a>
                        </li>
                    @endforeach
                    <li class="complete prev-step">
                        <a href="#"> --}}{{--<i class="ico fa fa-check ico-green">--}}{{--</i>
                            <span class="desc">Это Вы</span>
                        </a>
                    </li>
                    --}}{{--  <li class="complete prev-step">
                          <a href="#">Details <i class="ico fa fa-check ico-green"></i>
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li class="current">
                          <a href="#">Meta
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li>
                          <a href="#">Attributes
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li class="locked">
                          <a href="#">Locked <i class="ico fa fa-lock ico-muted"></i>
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li class="locked">
                          <a href="#">Images <i class="ico fa fa-lock ico-muted"></i>
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>--}}{{--
                </ul>
            </div>--}}
        </div>
    @endif
@else
    @isset($link)
        <div class="col-sm-6 col-md-6 side-content">
            <h4>Расстояние до заказчика:  {{$advert->getAgentsCount()}}</h4>
         {{--   <div class="bs-vertical-wizard">
                <ul>
                    @foreach($advert->buildAgents($link->advert->pivot->user_id) as $agent)
                        <li class="{{$loop->first ? 'complete' : 'prev-step'}}">
                            <a href="#">#{{$agent->id}} {!!  $loop->first ? '<i class="ico fa fa-check ico-green"></i>' : ''!!}
                                --}}{{-- <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>--}}{{--
                            </a>
                        </li>
                    @endforeach
                        <li class="complete prev-step">
                            <a href="#"> --}}{{--<i class="ico fa fa-check ico-green">--}}{{--</i>
                                <span class="desc">Это Вы</span>
                            </a>
                        </li>
                    --}}{{--  <li class="complete prev-step">
                          <a href="#">Details <i class="ico fa fa-check ico-green"></i>
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li class="current">
                          <a href="#">Meta
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li>
                          <a href="#">Attributes
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li class="locked">
                          <a href="#">Locked <i class="ico fa fa-lock ico-muted"></i>
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>
                      <li class="locked">
                          <a href="#">Images <i class="ico fa fa-lock ico-muted"></i>
                              <span class="desc">Lorem ipsum dolor sit amet, consectetur adipisicing elit. A, cumque.</span>
                          </a>
                      </li>--}}{{--
                </ul>
            </div>--}}
        </div>
    @endisset
@endif