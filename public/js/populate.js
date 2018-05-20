var apiUrl = "http://localhost:9000/src/";
var insertedRecords = 0;
var unProcessedCount = 0;
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

      for(var i=0; i < seedCount; i++) {
        populateDB(i);
      }
    });
  }

  function populateDB(fileNumber)
  {
    $.post(
      apiUrl+"app.php?action=populateDB",
      { "seedNumber" : fileNumber }
    ).done(function(results) {

      if(results.success) {
        insertedRecords += 1;
        $('#insertedRowsMsg').html(insertedRecords + " rows inserted into DB...");
      } else {
        allErrorMsg += results.error+"<br>";
        $('#errorRowsMsg').html(allErrorMsg);
        errorCount++;
      }

      unProcessedCount -=1;

      console.log("unProcessedCount:" + unProcessedCount);

      if(unProcessedCount === 0) {
        deleteFiles();
      }

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