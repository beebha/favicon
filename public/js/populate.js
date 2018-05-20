var apiUrl = "http://localhost:9000/src/";
var insertedRecords = 0;
var unProcessedCount = 0;
var processedCount = 0;
var recursedCount = 0;
var seedCount = 0;
var errorCount = 0;
var allErrorMsg = "";

$(function () {

  $('#populateBtn').click(function() {
    $('#populateBtn').hide();
    populate();
  });

  function populate()
  {
    $('#insertedRowsMsg').html("Processing data file and creating seed files for DB...");
    createCSVFiles();
  }

  function createCSVFiles()
  {
    $.post(
      apiUrl+"app.php?action=createCSVFiles"
    ).done(function(results) {

      seedCount = results.data.seedCount;
      unProcessedCount = seedCount;

      $('#insertedRowsMsg').html(seedCount+ " files created for seeding. Preparing for insert into DB...");
      recursePopulateDB();
    });
  }

  function recursePopulateDB()
  {
    for(var i=0; i < 10; i++) {
      populateDB(processedCount);
      processedCount++;
    }
  }

  function populateDB(fileNumber)
  {
    $.ajax({
      type: 'POST',
      url: apiUrl+"app.php?action=populateDB",
      data: { "seedNumber" : fileNumber },
      success: function(results) {

        if(results.success) {
          insertedRecords += 1;
          $('#insertedRowsMsg').html(insertedRecords + " rows inserted into DB...");
        } else {
          allErrorMsg += results.error+"<br>";
          $('#errorRowsMsg').html(allErrorMsg);
          errorCount++;
        }

        unProcessedCount -=1;
        recursedCount++;

        if(recursedCount === 10 && (processedCount < seedCount)) {
          recursedCount = 0;
          recursePopulateDB();
        }

        if(unProcessedCount === 0) {
          deleteFiles();
        }

      }
      // TODO - continue process of other requests if 1 times out
      // timeout: 15000
    });
  }

  function deleteFiles()
  {
    $.post(
      apiUrl+"app.php?action=deleteFiles",
      { "fileCount" : seedCount }
    ).done(function() {

      $('#insertedRowsMsg').html("");
      $('#completeMsg').html("A total of " + insertedRecords + " rows were inserted into DB. We. Are. Done!");

    });
  }
});