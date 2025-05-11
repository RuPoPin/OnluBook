var IndexValue = 0;
        function SlideShow (){
            setTimeout(SlideShow,4000);
            var x;
            const img = document.querySelectorAll(".side");
            for(x = 0; x < img.length; x++){
                img [x].style.display = "none";
            }
            IndexValue++;
            if(IndexValue > img.length){IndexValue = 1}
            img [IndexValue -1].style.display = "block";
        }
        SlideShow()