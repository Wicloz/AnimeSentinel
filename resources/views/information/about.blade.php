@extends('layouts.app')
@section('title', 'About')

@section('content-center')
  <div class="content-header">About</div>
  <div class="content-generic">
    <p>
      Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui.
    </p>
    <div class="content-close"></div>
  </div>


  <div class="content-header">Stage 1: Streaming</div>
  <div class="content-generic">
    <p>
      The first stage of this project involves making sure the dynamic streaming site portion works and is reasonably stable.
      After this, anime can be searched and added to our database.
      Episodes will be searched for in the background, and recently aired pages from streaming sites will be checked at regular intervals.
      Naturally, these episodes can be watched as well.
    </p>
    <h2>Features</h2>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Feature</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Create the anime details page.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Create the page to watch episodes.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Implement a method to order mirrors by quality.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Fix layout with 4:3 videos.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Add recently aired anime to the welcome page.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Add a random anime to the welcome page.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Allow searching for anime from MAL.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Replace MAL search results with entries from our database where possible.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Allow searching in only our database, only MAL, or both.</td>
          <td>Source selection needs to be more persistent</td>
        </tr>
        <tr>
          <td>Add buttons to the search results to add anime or add it and go to the details page.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Allow browsing of all anime in our database.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Allow browsing of recently aired epsiodes with different layouts.</td>
          <td>Layout selection needs to be more persistent</td>
        </tr>
        <tr>
          <td>Allow adding of animes and episodes.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Store most anime and episode data and update it only once it expires. AKA caching.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Allow tasks to be executed in the background using a queue.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Periodically check recently aired pages and add new episodes.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Make sure cloudflare-scrape works.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Solve kissanime's noCaptcha challenges.</td>
          <td>WIP</td>
        </tr>
        <tr>
          <td>Implement the download helper with retries and failures.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Send reports when an 'anomaly' is detected.</td>
          <td>Fix Mailserver</td>
        </tr>
        <tr>
          <td>Send reports for certain bugs and failures.</td>
          <td>Fix Mailserver</td>
        </tr>
        <tr>
          <td>Review classnames and filenames for the Jobs and AnimeSentinel stuff.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Properly handle in progress jobs when a new one gets queued.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>If a job is in progress and it's related task gets executed through other means, don't run the task but wait for the job to finish.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Fix 'Saiki Kusuo no Ψ-nan' not working on kissanime.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Improve anime title slugs.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Make anime data caching time more dynamic.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Make sure that delayed HD video uploads are handled properly.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Improve the way selection by 'title' or 'id' is handled.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Properly handle timezones.</td>
          <td>✘</td>
        </tr>
      </tbody>
    </table>
    <div class="content-close"></div>
  </div>

  <div class="content-header">Stage 2: Production</div>
  <div class="content-generic">
    <p>
      After this site can be used to at the very least watch anime, it is time to host it on a server in order to collect data and feedback.
      Also, a few less important features will require implementation.
    </p>
    <h2>Features</h2>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Feature</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Finish the about page.</td>
          <td>WIP</td>
        </tr>
        <tr>
          <td>Create the pages for streamer infromation.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Provide links to the multiple streamer related pages.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Show the hits counter at several locations.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Make disqus work.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Use jGrowl for the notification area.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Improve fluid layout, especially on mobile devices.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Properly scale the MAL widgets.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Decrease the size of the synopsy.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Make the description of synopsy really fancy.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Add more information to MAL search results.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Allow browsing of animes by first character.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Allow browsing/searching of animes by genre and type.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Create and implement the news/notification system.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Reduce the amount of excecuted queries.</td>
          <td>WIP</td>
        </tr>
        <tr>
          <td>Improve performance.</td>
          <td>WIP</td>
        </tr>
        <tr>
          <td>Use a smarter show cache expiration time.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Setup donations and ads.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Improve title based searches through our database.</td>
          <td>WIP</td>
        </tr>
        <tr>
          <td>Improve pages based on feedback.</td>
          <td>✘</td>
        </tr>
      </tbody>
    </table>
    <h2>Other</h2>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Feature</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Obtain a device to use as a server.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Setup that device properly.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Host this site on the server.</td>
          <td>✔</td>
        </tr>
      </tbody>
    </table>
    <div class="content-close"></div>
  </div>

  <div class="content-header">Stage 3: Users</div>
  <div class="content-generic">
    <p>
      This stage involves allowing people to create an account and link it to their MAL account.
      Afterwards, the notification system and other MAL interaction can be created.
    </p>
    <h2>Features</h2>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Feature</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Improve the login and register pages.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Create the users general options page.</td>
          <td>WIP</td>
        </tr>
        <tr>
          <td>Create the users mail notifications options page.</td>
          <td>WIP - Create a proper layout</td>
        </tr>
        <tr>
          <td>Create the users anime overview page.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Implement a permission system for the users.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Allow users to link their MAL account securely.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Notify users (based on their settings) when a new episode is available.</td>
          <td>WIP - Improve mail layout</td>
        </tr>
        <tr>
          <td>Change shows to 'watching' on users MAL accounts when an epsiode gets added.</td>
          <td>✔</td>
        </tr>
        <tr>
          <td>Add 'watched' marks next to episodes on anime detail pages.</td>
          <td>✔</td>
        </tr>
      </tbody>
    </table>
    <div class="content-close"></div>
  </div>

  <div class="content-header">Stage X: The Future</div>
  <div class="content-generic">
    <p>
      At this point the site can be considered 'fully functional' but is not finished yet.
      Now that the foundations have been built it is time to implement some really cool features that truly set this apart from other streaming sites.
    </p>
    <h2>Features</h2>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Feature</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Get video data such as resolution and playback length directly from the video file.</td>
          <td>WIP</td>
        </tr>
        <tr>
          <td>Allow videos to be played synchronously with friends.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Detect when a user has finished watching an episode and update ther MAL account.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Allow users to modify the colours of the site.</td>
          <td>✘</td>
        </tr>
        <tr>
          <td>Add many more streaming sites.</td>
          <td>✘</td>
        </tr>
      </tbody>
    </table>
    <div class="content-close"></div>
  </div>
@endsection
