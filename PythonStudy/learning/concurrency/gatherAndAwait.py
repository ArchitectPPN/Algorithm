import asyncio

async def doMathHomeWork():
    print("开始做数学家庭作业")
    await asyncio.sleep(5)
    print("Finished the Math homework")
    return "Math"

async def doEnglishHomeWork():
    print("开始做English家庭作业")
    await asyncio.sleep(3)
    print("Finished the English homework")
    return "English"

async def doArtHomeWork():
    print("开始做Art家庭作业")
    await asyncio.sleep(4)
    print("Finished the Art homework")
    return "Art"

async def doHomework():
    math, english, art = await asyncio.gather(
        doMathHomeWork(),
        doEnglishHomeWork(),
        doArtHomeWork(),
    )

    print(f"{english} {art} {math} was done!")

asyncio.run(doHomework())